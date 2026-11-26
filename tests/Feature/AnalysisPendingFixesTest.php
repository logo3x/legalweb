<?php

namespace Tests\Feature;

use App\Models\CaseEvent;
use App\Models\CaseFlowProgress;
use App\Models\DiscountCode;
use App\Models\Firm;
use App\Models\FlowStep;
use App\Models\LegalCase;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\TermDeadlineNotification;
use App\Services\AIModelRegistry;
use App\Services\AIPseudonymizer;
use App\Services\CaseSyncService;
use App\Services\SubscriptionActivator;
use App\Services\TybaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Pendientes del analisis de factibilidad: avisos de etapa, actuaciones del mismo dia,
 * doble canje de descuentos y radicado oculto a la IA.
 */
class AnalysisPendingFixesTest extends TestCase
{
    use RefreshDatabase;

    private Firm $firm;

    private User $lawyer;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        $this->firm = Firm::factory()->create();
        $this->lawyer = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'admin']);
    }

    private function makeCase(array $attributes = []): LegalCase
    {
        return LegalCase::factory()->create(array_merge([
            'firm_id' => $this->firm->id,
            'user_id' => $this->lawyer->id,
            'external_case_number' => '11001310300520240012300',
            'status' => 'abierto',
        ], $attributes));
    }

    public function test_stage_notices_count_business_days_from_when_the_step_started(): void
    {
        Notification::fake();
        $case = $this->makeCase();
        $step = FlowStep::factory()->create(['days_limit' => 5, 'name' => 'Subsanar demanda']);
        $pendingStep = FlowStep::factory()->create(['days_limit' => 1]);

        // Iniciado el martes 1 de diciembre de 2026: 5 dias habiles, saltando el festivo del 8 -> vence el 9.
        $this->travelTo('2026-12-01 10:00:00');
        $progress = CaseFlowProgress::create(['legal_case_id' => $case->id, 'flow_step_id' => $step->id, 'status' => 'en_progreso']);
        CaseFlowProgress::create(['legal_case_id' => $case->id, 'flow_step_id' => $pendingStep->id, 'status' => 'pendiente']);
        $this->assertNotNull($progress->fresh()->started_at);

        $this->travelTo('2026-12-03 08:00:00');
        $this->artisan('app:check-deadlines')->assertSuccessful();
        $this->artisan('app:check-deadlines')->assertSuccessful();
        Notification::assertSentToTimes($this->lawyer, TermDeadlineNotification::class, 1);
        Notification::assertSentTo($this->lawyer, TermDeadlineNotification::class, fn (TermDeadlineNotification $n): bool => $n->daysRemaining === 3 && $n->dueDate->toDateString() === '2026-12-09');

        // Editar el paso no reinicia el plazo.
        $progress->update(['notes' => 'Revisado']);

        $this->travelTo('2026-12-04 08:00:00');
        $this->artisan('app:check-deadlines')->assertSuccessful();
        Notification::assertSentToTimes($this->lawyer, TermDeadlineNotification::class, 1);

        // Si un dia no corre el cron, el aviso del dia del vencimiento igual sale.
        $this->travelTo('2026-12-09 08:00:00');
        $this->artisan('app:check-deadlines')->assertSuccessful();
        Notification::assertSentToTimes($this->lawyer, TermDeadlineNotification::class, 2);
        Notification::assertSentTo($this->lawyer, TermDeadlineNotification::class, fn (TermDeadlineNotification $n): bool => $n->daysRemaining === 0);
        $this->assertEqualsCanonicalizing([0, 1, 3], $progress->fresh()->deadline_notices);
    }

    /**
     * @return array<string, mixed>
     */
    private function actuacion(string $ref, string $anotacion): array
    {
        return [
            'ref' => $ref, 'ciclo' => '', 'tipo' => 'Fijacion estado', 'fecha' => '2026-11-20',
            'fecha_registro' => '2026-11-20', 'fecha_inicial' => '', 'fecha_final' => '',
            'anotacion' => $anotacion, 'cod_regla' => '', 'con_documentos' => false,
        ];
    }

    public function test_two_actuaciones_with_the_same_title_on_the_same_day_are_both_kept(): void
    {
        Notification::fake();
        $case = $this->makeCase(['tyba_data' => ['despacho' => 'Juzgado 5 Civil']]);

        // Evento guardado por una version anterior del sync (sin id de la Rama).
        CaseEvent::create(['legal_case_id' => $case->id, 'title' => 'Fijacion estado', 'event_date' => '2026-11-20', 'event_type' => 'actuacion']);

        $this->mock(TybaService::class, fn (MockInterface $mock) => $mock->shouldReceive('extractProcessInfo')->andReturn([
            'tipo_proceso' => 'Verbal', 'clase_proceso' => '', 'subclase' => '', 'departamento' => '', 'despacho' => 'Juzgado 5 Civil',
            'ponente' => '', 'ubicacion' => '', 'recurso' => '', 'sujetos' => [],
            'actuaciones' => [$this->actuacion('r:1001', 'Auto admite'), $this->actuacion('r:1002', 'Auto decreta pruebas')],
        ]));

        app(CaseSyncService::class)->sync($case);
        app(CaseSyncService::class)->sync($case);

        $events = CaseEvent::where('legal_case_id', $case->id)->orderBy('id')->get();
        $this->assertCount(2, $events, 'La actuacion antigua se adopta y la segunda se crea, sin duplicar al resincronizar.');
        $this->assertSame(['r:1001', 'r:1002'], $events->pluck('rama_ref')->all());
    }

    public function test_single_use_discount_cannot_be_redeemed_twice(): void
    {
        $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'price_monthly' => 50000, 'price_yearly' => 250000, 'is_active' => true]);
        $code = DiscountCode::create(['code' => 'UNAVEZ', 'type' => DiscountCode::TYPE_PERCENT, 'amount' => 20, 'max_uses' => 1, 'current_uses' => 0, 'is_active' => true]);
        $otherFirm = Firm::factory()->create();
        $otherAdmin = User::factory()->create(['firm_id' => $otherFirm->id, 'role' => 'admin']);

        $this->actingAs($this->lawyer)->post(route('wompi.checkout'), ['plan_id' => $plan->id, 'billing_cycle' => 'monthly', 'discount_code' => 'UNAVEZ'])->assertRedirect();
        $this->actingAs($otherAdmin)->post(route('wompi.checkout'), ['plan_id' => $plan->id, 'billing_cycle' => 'monthly', 'discount_code' => 'UNAVEZ'])
            ->assertRedirect('/admin/planes')
            ->assertSessionHas('error');

        $this->assertSame(1, Subscription::where('discount_code_id', $code->id)->count());

        // Aunque dos pagos con el codigo se aprueben, el contador no supera el limite.
        $first = Subscription::where('discount_code_id', $code->id)->first();
        $second = $first->replicate()->fill(['wompi_reference' => 'LEGALWEB-otra-ref']);
        $second->save();

        foreach ([$first, $second] as $subscription) {
            app(SubscriptionActivator::class)->activate($subscription->fresh(), [
                'id' => 'tx-'.$subscription->id, 'status' => 'APPROVED', 'reference' => $subscription->wompi_reference,
                'currency' => 'COP', 'amount_in_cents' => $subscription->amount_in_cents,
            ]);
        }

        $this->assertSame(1, $code->fresh()->current_uses);
    }

    public function test_the_radicado_is_hidden_from_the_ai_provider(): void
    {
        $case = $this->makeCase(['case_number' => 'LW-0042-2026']);
        $pseudonymizer = AIPseudonymizer::forCase($case);

        $masked = $pseudonymizer->mask('Proceso 11001310300520240012300 (LW-0042-2026), acumulado con el 05001-31-05-001-2023-00456-00.');

        $this->assertStringNotContainsString('11001310300520240012300', $masked);
        $this->assertStringNotContainsString('LW-0042-2026', $masked);
        $this->assertStringNotContainsString('2023-00456', $masked);
        $this->assertStringContainsString('[RADICADO]', $masked);
        $this->assertSame('Proceso 11001310300520240012300 (LW-0042-2026), acumulado con el 05001-31-05-001-2023-00456-00.', $pseudonymizer->restore($masked));
    }

    public function test_training_providers_can_be_switched_off(): void
    {
        config([
            'services.gemini.api_key' => 'g', 'services.openrouter.api_key' => 'o',
            'services.openrouter.data_collection' => 'deny', 'services.nvidia.api_key' => null,
        ]);
        Cache::forever(AIModelRegistry::CACHE_KEY, [
            ['key' => 'openrouter:acme/free:free', 'label' => 'Privado', 'verified_at' => ''],
            ['key' => 'gemini:gemini-flash-latest', 'label' => 'Gemini', 'verified_at' => ''],
        ]);

        $this->assertArrayHasKey('gemini:gemini-flash-latest', app(AIModelRegistry::class)->options());

        config(['services.ai.only_private_providers' => true]);

        $this->assertSame(['openrouter:acme/free:free'], array_keys(app(AIModelRegistry::class)->options()));
    }
}
