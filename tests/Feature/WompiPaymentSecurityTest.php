<?php

namespace Tests\Feature;

use App\Models\DiscountCode;
use App\Models\DiscountRedemption;
use App\Models\Firm;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WompiPaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'prod_events_secreto_de_prueba';

    private const REFERENCE = 'LEGALWEB-1-pro-1760000000';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.wompi.events_secret' => self::SECRET, 'services.wompi.origin' => 'legalweb']);
    }

    private function pendingSubscription(array $attributes = []): Subscription
    {
        $firm = Firm::factory()->create();
        $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'price_monthly' => 50000, 'price_yearly' => 250000]);

        return Subscription::create(array_merge([
            'firm_id' => $firm->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'amount_in_cents' => 5000000,
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'wompi_reference' => self::REFERENCE,
        ], $attributes));
    }

    /**
     * Evento "transaction.updated" firmado como lo hace Wompi. La API de Wompi (simulada)
     * responde con la transaccion real, que por defecto coincide con la del evento.
     *
     * @return array<string, mixed>
     */
    private function event(array $transaction = [], ?string $secret = self::SECRET, ?array $apiTransaction = null): array
    {
        $transaction = array_merge([
            'id' => '1234-1700000000-00001',
            'status' => 'APPROVED',
            'reference' => self::REFERENCE,
            'amount_in_cents' => 5000000,
            'currency' => 'COP',
        ], $transaction);

        Http::fake(['*/transactions/*' => Http::response(['data' => $apiTransaction ?? $transaction])]);

        $properties = ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'];
        $timestamp = 1760000000;
        $checksum = hash('sha256', $transaction['id'].$transaction['status'].$transaction['amount_in_cents'].$timestamp.$secret);

        return [
            'event' => 'transaction.updated',
            'data' => ['transaction' => $transaction],
            'signature' => ['properties' => $properties, 'checksum' => $checksum],
            'timestamp' => $timestamp,
        ];
    }

    public function test_valid_webhook_activates_pending_subscription(): void
    {
        $subscription = $this->pendingSubscription();

        $this->postJson(route('wompi.webhook'), $this->event())->assertOk();

        $this->assertSame('active', $subscription->fresh()->status);
    }

    public function test_webhook_ignores_unsigned_reference_and_uses_wompi_api(): void
    {
        $victim = $this->pendingSubscription();
        $attacker = Subscription::create([
            'firm_id' => Firm::factory()->create()->id,
            'plan_id' => $victim->plan_id,
            'billing_cycle' => 'monthly',
            'amount_in_cents' => 5000000,
            'status' => 'pending',
            'starts_at' => now(),
            'wompi_reference' => 'LEGALWEB-2-pro-sin-pagar',
        ]);

        // Evento real (pagado por la victima) con la referencia cambiada: la firma sigue siendo valida
        // porque la referencia no esta entre las propiedades firmadas.
        $real = ['id' => '1234-1700000000-00001', 'status' => 'APPROVED', 'reference' => self::REFERENCE, 'amount_in_cents' => 5000000, 'currency' => 'COP'];
        $this->postJson(route('wompi.webhook'), $this->event(['reference' => $attacker->wompi_reference], self::SECRET, $real))->assertOk();

        $this->assertSame('pending', $attacker->fresh()->status);
        $this->assertSame('active', $victim->fresh()->status);
    }

    public function test_webhook_is_retried_when_wompi_api_is_unavailable(): void
    {
        $subscription = $this->pendingSubscription();
        // El primer stub registrado gana: la API responde con error.
        Http::fake(['*' => Http::response([], 500)]);
        $event = $this->event();

        $this->postJson(route('wompi.webhook'), $event)->assertStatus(503);

        $this->assertSame('pending', $subscription->fresh()->status);
    }

    public function test_callback_rejects_malformed_transaction_ids(): void
    {
        Http::fake();
        $user = User::factory()->create(['firm_id' => Firm::factory()->create()->id, 'role' => 'admin']);

        $this->actingAs($user)->get('/wompi/callback?id=../../merchants/x')->assertRedirect('/admin/planes');

        Http::assertNothingSent();
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        $subscription = $this->pendingSubscription();

        $this->postJson(route('wompi.webhook'), $this->event([], 'secreto-adivinado'))->assertUnauthorized();

        $this->assertSame('pending', $subscription->fresh()->status);
    }

    public function test_webhook_is_rejected_when_events_secret_is_not_configured(): void
    {
        config(['services.wompi.events_secret' => '']);
        $subscription = $this->pendingSubscription();

        // Con el secreto vacio, la firma calculada sin secreto seria "valida": debe rechazarse igual.
        $this->postJson(route('wompi.webhook'), $this->event([], ''))->assertStatus(503);

        $this->assertSame('pending', $subscription->fresh()->status);
    }

    public function test_webhook_with_different_amount_does_not_activate(): void
    {
        $subscription = $this->pendingSubscription();

        $this->postJson(route('wompi.webhook'), $this->event(['amount_in_cents' => 100]))->assertOk();

        $this->assertSame('pending', $subscription->fresh()->status);
    }

    public function test_webhook_with_different_currency_does_not_activate(): void
    {
        $subscription = $this->pendingSubscription();

        $this->postJson(route('wompi.webhook'), $this->event(['currency' => 'USD']))->assertOk();

        $this->assertSame('pending', $subscription->fresh()->status);
    }

    public function test_replayed_event_does_not_reactivate_canceled_subscription(): void
    {
        $subscription = $this->pendingSubscription(['status' => 'canceled']);

        $this->postJson(route('wompi.webhook'), $this->event())->assertOk();

        $this->assertSame('canceled', $subscription->fresh()->status);
    }

    public function test_activation_expires_previous_active_subscription_of_the_firm(): void
    {
        $subscription = $this->pendingSubscription();
        $previous = Subscription::create([
            'firm_id' => $subscription->firm_id,
            'plan_id' => $subscription->plan_id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'wompi_reference' => 'LEGALWEB-1-pro-anterior',
        ]);

        $this->postJson(route('wompi.webhook'), $this->event())->assertOk();

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame('expired', $previous->fresh()->status);
    }

    public function test_checkout_does_not_accept_get_requests(): void
    {
        $user = User::factory()->create(['firm_id' => Firm::factory()->create()->id, 'role' => 'admin']);

        $this->actingAs($user)->get('/wompi/checkout?plan_id=1&billing_cycle=monthly')->assertMethodNotAllowed();
    }

    public function test_discount_code_is_only_consumed_when_payment_is_approved(): void
    {
        $firm = Firm::factory()->create();
        $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'price_monthly' => 50000, 'price_yearly' => 250000]);
        $user = User::factory()->create(['firm_id' => $firm->id, 'role' => 'admin']);
        $code = DiscountCode::create(['code' => 'LANZAMIENTO', 'type' => DiscountCode::TYPE_PERCENT, 'amount' => 20, 'max_uses' => 1, 'current_uses' => 0, 'is_active' => true]);

        // Abrir el checkout varias veces sin pagar no gasta el codigo.
        foreach (range(1, 3) as $attempt) {
            $this->actingAs($user)
                ->post(route('wompi.checkout'), ['plan_id' => $plan->id, 'billing_cycle' => 'monthly', 'discount_code' => 'LANZAMIENTO'])
                ->assertRedirect();
        }

        $this->assertSame(0, $code->fresh()->current_uses);
        $this->assertSame(0, DiscountRedemption::count());

        $subscription = Subscription::where('firm_id', $firm->id)->latest('id')->first();
        $this->assertSame(4000000, $subscription->amount_in_cents);

        $this->postJson(route('wompi.webhook'), $this->event([
            'reference' => $subscription->wompi_reference,
            'amount_in_cents' => 4000000,
        ]))->assertOk();

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame(1, $code->fresh()->current_uses);
        $this->assertDatabaseHas('discount_redemptions', [
            'discount_code_id' => $code->id,
            'firm_id' => $firm->id,
            'original_amount' => 50000,
            'discount_amount' => 10000,
            'final_amount' => 40000,
        ]);
    }

    public function test_checkout_stores_expected_amount(): void
    {
        $firm = Firm::factory()->create();
        $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'price_monthly' => 50000, 'price_yearly' => 250000]);
        $user = User::factory()->create(['firm_id' => $firm->id, 'role' => 'admin']);

        $this->actingAs($user)
            ->post(route('wompi.checkout'), ['plan_id' => $plan->id, 'billing_cycle' => 'monthly'])
            ->assertRedirect();

        $this->assertSame(5000000, Subscription::where('firm_id', $firm->id)->value('amount_in_cents'));
    }

    public function test_checkout_redirects_to_the_wompi_web_checkout_with_a_valid_signature(): void
    {
        config(['services.wompi.public_key' => 'pub_test_abc', 'services.wompi.integrity_secret' => 'test_integrity_x', 'services.wompi.sandbox' => true]);
        $firm = Firm::factory()->create();
        $user = User::factory()->create(['firm_id' => $firm->id, 'role' => 'admin']);
        $plan = Plan::create(['name' => 'Individual', 'slug' => 'individual', 'price_monthly' => 59900, 'price_yearly' => 299500, 'is_active' => true]);

        $response = $this->actingAs($user)->post(route('wompi.checkout'), ['plan_id' => $plan->id, 'billing_cycle' => 'monthly']);

        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith('https://checkout.wompi.co/p/?', $location, 'Web Checkout: la misma URL en pruebas y produccion.');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $reference = Subscription::where('firm_id', $firm->id)->sole()->wompi_reference;
        $this->assertSame('pub_test_abc', $query['public-key']);
        $this->assertSame('5990000', $query['amount-in-cents']);
        $this->assertSame($reference, $query['reference']);
        $this->assertSame(hash('sha256', $reference.'5990000COPtest_integrity_x'), $query['signature:integrity']);
    }
}
