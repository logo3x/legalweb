<?php

namespace Tests\Feature;

use App\Filament\Pages\UpgradePlan;
use App\Models\Firm;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\AutoRenewFailedNotification;
use App\Notifications\SubscriptionStatusNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class WompiAutoRenewTest extends TestCase
{
    use RefreshDatabase;

    private Firm $firm;

    private User $admin;

    private Plan $plan;

    private Subscription $current;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        config([
            'services.wompi.recurring_enabled' => true,
            'services.wompi.public_key' => 'pub_test_abc',
            'services.wompi.private_key' => 'prv_test_abc',
            'services.wompi.integrity_secret' => 'test_integrity_secreto',
            'services.wompi.base_url' => 'https://sandbox.wompi.co/v1',
            'services.wompi.origin' => 'legalweb',
        ]);

        $this->firm = Firm::factory()->create();
        $this->admin = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'admin']);
        $this->plan = Plan::create(['name' => 'Individual', 'slug' => 'individual', 'price_monthly' => 59900, 'price_yearly' => 299500, 'max_cases' => 80, 'max_users' => 1, 'is_active' => true]);
        $this->current = Subscription::create([
            'firm_id' => $this->firm->id, 'plan_id' => $this->plan->id, 'billing_cycle' => 'monthly',
            'amount_in_cents' => 4790000, 'status' => 'active', 'starts_at' => now()->subMonth(),
            'ends_at' => now()->addHours(20), 'wompi_reference' => 'LEGALWEB-1-individual-primero',
        ]);
    }

    private function withCard(): void
    {
        $this->firm->forceFill([
            'wompi_payment_source_id' => '3891', 'card_brand' => 'VISA', 'card_last_four' => '4242',
            'card_owner_email' => $this->admin->email, 'auto_renew_authorized_at' => now(),
        ])->save();
    }

    public function test_admin_registers_a_card_with_only_the_wompi_token(): void
    {
        Http::fake([
            '*/merchants/info' => Http::response(['data' => [
                'presigned_acceptance' => ['acceptance_token' => 'acc_tok', 'permalink' => 'https://wompi.co/terminos.pdf'],
                'presigned_personal_data_auth' => ['acceptance_token' => 'pers_tok', 'permalink' => 'https://wompi.co/datos.pdf'],
            ]]),
            '*/payment_sources' => Http::response(['data' => ['id' => 3891, 'status' => 'AVAILABLE', 'public_data' => ['brand' => 'VISA', 'last_four' => '4242']]]),
        ]);
        $this->actingAs($this->admin);

        Livewire::test(UpgradePlan::class)
            ->assertSee('Pago automático de la renovación')
            ->assertSee('el cobro aparecerá en mi extracto como', false)
            ->assertSee('CITORA')
            ->call('registerCard', 'tok_test_123', 'acc_tok', 'pers_tok', false)
            ->assertNotified('Debe autorizar el cobro automático')
            ->call('registerCard', 'tok_test_123', 'acc_tok', 'pers_tok', true)
            ->assertNotified('Pago automático activado');

        $this->firm->refresh();
        $this->assertTrue($this->firm->hasAutoRenewCard());
        $this->assertSame('4242', $this->firm->card_last_four);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/payment_sources')
            && $request->hasHeader('Authorization', 'Bearer prv_test_abc')
            && $request['token'] === 'tok_test_123'
            && $request['acceptance_token'] === 'acc_tok'
            && $request['accept_personal_auth'] === 'pers_tok');

        Livewire::test(UpgradePlan::class)->call('removeCard');
        $this->assertFalse($this->firm->fresh()->hasAutoRenewCard());
    }

    public function test_renewal_is_charged_the_day_before_and_activated_when_approved(): void
    {
        $this->withCard();
        Http::fake(['*/transactions' => fn (Request $request) => Http::response(['data' => [
            'id' => 'tx-1', 'status' => 'APPROVED', 'reference' => $request['reference'],
            'amount_in_cents' => $request['amount_in_cents'], 'currency' => 'COP',
        ]])]);

        $this->artisan('app:charge-auto-renewals')->assertSuccessful();

        $renewal = Subscription::where('is_auto_renewal', true)->sole();
        $this->assertSame('active', $renewal->status);
        $this->assertSame(5990000, $renewal->amount_in_cents, 'Precio vigente del plan, sin el descuento del primer pago.');
        $this->assertTrue($renewal->starts_at->equalTo($this->current->ends_at));
        $this->assertSame('expired', $this->current->fresh()->status);

        Http::assertSent(function (Request $request) use ($renewal): bool {
            return $request['payment_source_id'] === 3891
                && $request['recurrent'] === true
                && $request['payment_method'] === ['installments' => 1]
                && $request['signature'] === hash('sha256', $renewal->wompi_reference.'5990000COPtest_integrity_secreto');
        });

        // Al dia siguiente no se vuelve a cobrar.
        $this->artisan('app:charge-auto-renewals')->assertSuccessful();
        Http::assertSentCount(1);
    }

    public function test_declined_renewal_notifies_and_retries_at_most_three_times(): void
    {
        Notification::fake();
        $this->withCard();
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'tx-x', 'status' => 'DECLINED']])]);

        foreach (range(1, 4) as $day) {
            $this->artisan('app:charge-auto-renewals')->assertSuccessful();
            $this->travel(21)->hours();
        }

        Http::assertSentCount(3);
        $this->assertSame(3, $this->firm->fresh()->auto_renew_failures);
        Notification::assertSentToTimes($this->admin, AutoRenewFailedNotification::class, 3);
        Notification::assertSentTo($this->admin, AutoRenewFailedNotification::class, fn (AutoRenewFailedNotification $n): bool => $n->willRetry === false);
    }

    public function test_nothing_is_charged_when_auto_renew_is_disabled(): void
    {
        config(['services.wompi.recurring_enabled' => false]);
        $this->withCard();
        Http::fake();

        $this->artisan('app:charge-auto-renewals')->assertSuccessful();

        Http::assertNothingSent();
        $this->actingAs($this->admin);
        Livewire::test(UpgradePlan::class)->assertDontSee('Pago automático de la renovación');
    }

    public function test_upcoming_notice_announces_the_automatic_charge(): void
    {
        $this->withCard();

        $html = (string) (new SubscriptionStatusNotification($this->current, SubscriptionStatusNotification::STAGE_UPCOMING))->toMail($this->admin)->render();

        $this->assertStringContainsString('cobraremos la renovación automáticamente', mb_strtolower($html));
        $this->assertStringContainsString('VISA terminada en 4242', $html);
        $this->assertStringContainsString('en su extracto aparecerá como CITORA', $html);

        config(['services.wompi.statement_name' => 'LEGALWEB']);
        $html = (string) (new SubscriptionStatusNotification($this->current, SubscriptionStatusNotification::STAGE_UPCOMING))->toMail($this->admin)->render();
        $this->assertStringContainsString('en su extracto aparecerá como LEGALWEB', $html);
    }
}
