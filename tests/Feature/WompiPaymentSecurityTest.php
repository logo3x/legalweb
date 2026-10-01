<?php

namespace Tests\Feature;

use App\Models\Firm;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
     * Evento "transaction.updated" firmado como lo hace Wompi.
     *
     * @return array<string, mixed>
     */
    private function event(array $transaction = [], ?string $secret = self::SECRET): array
    {
        $transaction = array_merge([
            'id' => '1234-1700000000-00001',
            'status' => 'APPROVED',
            'reference' => self::REFERENCE,
            'amount_in_cents' => 5000000,
            'currency' => 'COP',
        ], $transaction);

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
}
