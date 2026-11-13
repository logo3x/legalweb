<?php

namespace Tests\Feature;

use App\Filament\Pages\TeamMembers;
use App\Filament\Resources\LegalCases\Pages\CreateLegalCase;
use App\Filament\Resources\LegalCases\Pages\EditLegalCase;
use App\Filament\Widgets\PlanOverview;
use App\Jobs\SyncCaseActuaciones;
use App\Models\AiUsageLog;
use App\Models\Client;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\PlanLimitsNoticeNotification;
use App\Notifications\TybaSyncNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class PlanLimitsTest extends TestCase
{
    use RefreshDatabase;

    private Plan $free;

    private Plan $pro;

    private Plan $firmPlan;

    private Firm $firm;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Http::fake();

        $this->free = Plan::create(['name' => 'Gratuito', 'slug' => 'gratuito', 'price_monthly' => 0, 'price_yearly' => 0, 'max_cases' => 5, 'max_users' => 1, 'ai_monthly_limit' => 10, 'has_whatsapp' => false, 'has_client_monitoring' => false, 'sync_every_days' => 7, 'is_active' => true]);
        $this->pro = Plan::create(['name' => 'Profesional', 'slug' => 'pro', 'price_monthly' => 120000, 'price_yearly' => 600000, 'max_cases' => 0, 'max_users' => 3, 'is_active' => true]);
        $this->firmPlan = Plan::create(['name' => 'Firma', 'slug' => 'firma', 'price_monthly' => 250000, 'price_yearly' => 1250000, 'max_cases' => 0, 'max_users' => 10, 'is_active' => true]);

        $this->firm = Firm::factory()->create();
        $this->admin = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'admin']);
    }

    private function subscribe(Plan $plan, array $attributes = []): Subscription
    {
        return Subscription::create([
            'firm_id' => $this->firm->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMonths(4),
            ...$attributes,
        ]);
    }

    private function onFreeLimits(): void
    {
        $this->travelTo('2027-01-15 10:00:00');
        $this->subscribe($this->free, ['trial_ends_at' => now()->subMonth()]);
    }

    private function createActiveCases(int $count): void
    {
        $client = Client::factory()->create(['firm_id' => $this->firm->id]);
        LegalCase::factory()->count($count)->create(['firm_id' => $this->firm->id, 'client_id' => $client->id, 'status' => 'abierto', 'is_demo' => false]);
    }

    public function test_tiers_follow_the_subscription(): void
    {
        $this->travelTo('2027-01-15 10:00:00');
        $trial = $this->subscribe($this->free, ['trial_ends_at' => now()->addMonth()]);

        $this->assertSame('trial', $this->firm->planLimits()->tier());
        $this->assertNull($this->firm->planLimits()->maxActiveCases());
        $this->assertSame(3, $this->firm->planLimits()->maxUsers());

        $trial->update(['trial_ends_at' => now()->subDay()]);
        $this->assertSame('free', $this->firm->planLimits()->tier());
        $this->assertSame(5, $this->firm->planLimits()->maxActiveCases());
        $this->assertFalse($this->firm->planLimits()->hasWhatsApp());

        $this->subscribe($this->pro);
        $this->assertSame('paid', $this->firm->planLimits()->tier());
        $this->assertNull($this->firm->planLimits()->maxActiveCases());
        $this->assertTrue($this->firm->planLimits()->hasClientMonitoring());
    }

    public function test_firms_whose_trial_already_ended_get_until_december_12(): void
    {
        $this->travelTo('2026-11-20 10:00:00');
        $this->subscribe($this->free, ['trial_ends_at' => now()->subMonths(2)]);

        $this->assertSame('trial', $this->firm->planLimits()->tier());
        $this->assertSame('2026-12-12', $this->firm->planLimits()->limitsStartAt()->toDateString());
    }

    public function test_free_plan_blocks_the_sixth_active_case_but_keeps_existing_ones(): void
    {
        $this->onFreeLimits();
        $this->createActiveCases(5);
        $this->actingAs($this->admin);

        Livewire::test(CreateLegalCase::class)->assertRedirect();

        $closed = LegalCase::where('firm_id', $this->firm->id)->first();
        $closed->update(['status' => 'cerrado']);

        // Con un cupo libre se puede reabrir; despues ya no.
        $this->assertTrue($this->firm->planLimits()->canAddActiveCase());
        $closed->update(['status' => 'abierto']);
        $this->assertFalse($this->firm->planLimits()->canAddActiveCase());

        $this->assertSame(5, LegalCase::where('firm_id', $this->firm->id)->where('status', 'abierto')->count());
    }

    public function test_reopening_a_case_over_the_limit_is_blocked(): void
    {
        $this->onFreeLimits();
        $this->createActiveCases(5);
        $client = Client::where('firm_id', $this->firm->id)->first();
        $closed = LegalCase::factory()->create(['firm_id' => $this->firm->id, 'client_id' => $client->id, 'user_id' => $this->admin->id, 'status' => 'cerrado', 'is_demo' => false]);

        $this->actingAs($this->admin);

        Livewire::test(EditLegalCase::class, ['record' => $closed->getRouteKey()])
            ->fillForm(['status' => 'abierto'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Alcanzo el limite de casos activos');

        $this->assertSame('cerrado', $closed->fresh()->status);
    }

    public function test_paid_plan_has_no_case_limit(): void
    {
        $this->travelTo('2027-01-15 10:00:00');
        $this->subscribe($this->pro);
        $this->createActiveCases(6);

        $this->assertTrue($this->firm->planLimits()->canAddActiveCase());
    }

    public function test_free_plan_limits_team_invitations(): void
    {
        $this->onFreeLimits();
        $this->actingAs($this->admin);

        Livewire::test(TeamMembers::class)
            ->callAction('invite', ['email' => 'nuevo@example.com', 'role' => 'abogado'])
            ->assertNotified('Alcanzo el limite de usuarios de su plan');

        $this->assertDatabaseMissing('firm_invitations', ['email' => 'nuevo@example.com']);
    }

    public function test_free_plan_has_ten_ai_queries_per_month(): void
    {
        $this->onFreeLimits();

        for ($i = 0; $i < 10; $i++) {
            AiUsageLog::create(['firm_id' => $this->firm->id, 'action' => 'resumen', 'provider' => 'NVIDIA', 'success' => true, 'created_at' => now()]);
        }

        $this->assertFalse($this->firm->planLimits()->canUseAi());

        $this->travelTo(now()->addMonth()->startOfMonth()->addDay());
        $this->assertTrue($this->firm->planLimits()->canUseAi());
    }

    public function test_free_plan_does_not_send_whatsapp(): void
    {
        config(['services.twilio.enabled' => true, 'services.twilio.sid' => 'AC123', 'services.twilio.auth_token' => 'tok', 'services.twilio.whatsapp_from' => '+14155238886']);
        $this->onFreeLimits();
        $this->admin->update(['whatsapp_enabled' => true, 'whatsapp_phone' => '3001234567']);
        $client = Client::factory()->create(['firm_id' => $this->firm->id]);
        $case = LegalCase::factory()->create(['firm_id' => $this->firm->id, 'client_id' => $client->id]);

        $this->assertNotContains(WhatsAppChannel::class, (new TybaSyncNotification($case, 1))->via($this->admin->fresh()));

        // Con un plan de pago si se envia.
        $this->subscribe($this->pro);
        $this->assertContains(WhatsAppChannel::class, (new TybaSyncNotification($case, 1))->via($this->admin->fresh()));
    }

    public function test_free_plan_syncs_weekly(): void
    {
        Queue::fake();
        $this->onFreeLimits();
        $client = Client::factory()->create(['firm_id' => $this->firm->id]);
        LegalCase::factory()->create(['firm_id' => $this->firm->id, 'client_id' => $client->id, 'status' => 'abierto', 'external_case_number' => '11001310300120250000100', 'last_tyba_sync' => now()->subDays(3)]);
        LegalCase::factory()->create(['firm_id' => $this->firm->id, 'client_id' => $client->id, 'status' => 'abierto', 'external_case_number' => '11001310300120250000200', 'last_tyba_sync' => now()->subDays(8)]);

        $this->artisan('app:sync-tyba-actuaciones')->assertSuccessful();

        Queue::assertPushed(SyncCaseActuaciones::class, 1);
    }

    public function test_notice_is_sent_thirty_and_three_days_before_limits(): void
    {
        Notification::fake();
        $this->travelTo('2026-11-12 09:00:00');
        $this->subscribe($this->free, ['trial_ends_at' => now()->subMonth()]);

        $this->artisan('app:notify-plan-limits')->assertSuccessful();
        $this->artisan('app:notify-plan-limits')->assertSuccessful();
        Notification::assertSentToTimes($this->admin, PlanLimitsNoticeNotification::class, 1);

        $this->travelTo('2026-12-10 09:00:00');
        $this->artisan('app:notify-plan-limits')->assertSuccessful();

        Notification::assertSentToTimes($this->admin, PlanLimitsNoticeNotification::class, 2);
        Notification::assertSentTo($this->admin, PlanLimitsNoticeNotification::class, fn ($n) => $n->stage === PlanLimitsNoticeNotification::STAGE_FINAL);
    }

    public function test_checkout_rejects_inactive_or_free_plans(): void
    {
        $this->subscribe($this->free, ['trial_ends_at' => now()->addMonth()]);
        $legacy = Plan::create(['name' => 'Viejo', 'slug' => 'viejo', 'price_monthly' => 69900, 'price_yearly' => 349000, 'max_users' => 10, 'is_active' => false]);
        $this->actingAs($this->admin);

        $this->post(route('wompi.checkout'), ['plan_id' => $legacy->id, 'billing_cycle' => 'monthly'])->assertSessionHasErrors('plan_id');
        $this->post(route('wompi.checkout'), ['plan_id' => $this->free->id, 'billing_cycle' => 'monthly'])->assertSessionHasErrors('plan_id');
        $this->assertDatabaseMissing('subscriptions', ['plan_id' => $legacy->id]);
    }

    public function test_plan_page_shows_the_three_plans(): void
    {
        $this->subscribe($this->free, ['trial_ends_at' => now()->addMonth()]);

        $this->actingAs($this->admin)->get('/admin/planes')
            ->assertOk()
            ->assertSee('Gratuito')
            ->assertSee('Profesional')
            ->assertSee('Firma')
            ->assertSee('$250.000');
    }

    public function test_plan_widget_renders_for_each_tier(): void
    {
        $this->travelTo('2027-01-15 10:00:00');
        $subscription = $this->subscribe($this->free, ['trial_ends_at' => now()->addDays(20)]);
        $this->actingAs($this->admin);

        Livewire::test(PlanOverview::class)->assertSee('Prueba gratuita')->assertSee('20 días de prueba');

        $subscription->update(['trial_ends_at' => now()->subDay()]);
        Livewire::test(PlanOverview::class)->assertSee('Plan Gratuito')->assertSee('0 de 5 casos activos')->assertSee('Mejorar plan');

        $this->subscribe($this->firmPlan);
        Livewire::test(PlanOverview::class)->assertSee('Plan Firma')->assertSee('Casos ilimitados')->assertDontSee('Mejorar plan');
    }
}
