<?php

namespace Tests\Feature;

use App\Filament\Pages\DeadlinesDashboard;
use App\Filament\Resources\Reminders\Pages\ListReminders;
use App\Models\CaseFlowProgress;
use App\Models\CasePermission;
use App\Models\Client;
use App\Models\Firm;
use App\Models\FlowStep;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Models\User;
use App\Services\CasePendingSummary;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeadlinesAndAgendaUxTest extends TestCase
{
    use RefreshDatabase;

    private Firm $firm;

    private User $admin;

    private LegalCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->travelTo(now()->setDate(2026, 11, 23)->setTime(9, 0)); // lunes habil, lejos de la vacancia

        $this->firm = Firm::factory()->create();
        $this->admin = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'admin']);
        $client = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id]);
        $this->case = LegalCase::factory()->create([
            'firm_id' => $this->firm->id,
            'user_id' => $this->admin->id,
            'client_id' => $client->id,
            'is_demo' => false,
            'status' => 'en_progreso',
            'case_number' => 'LW-0001-2026',
        ]);
    }

    private function runningStep(string $name, int $days, ?LegalCase $case = null): void
    {
        CaseFlowProgress::factory()->create([
            'legal_case_id' => ($case ?? $this->case)->id,
            'flow_step_id' => FlowStep::factory()->create(['name' => $name, 'days_limit' => $days])->id,
            'status' => 'en_progreso',
            'started_at' => now(),
        ]);
    }

    private function reminder(string $title, string $type, int $days): Reminder
    {
        return Reminder::factory()->create([
            'firm_id' => $this->firm->id,
            'user_id' => $this->admin->id,
            'legal_case_id' => $this->case->id,
            'title' => $title,
            'type' => $type,
            'due_date' => now()->addDays($days)->setTime(17, 0),
            'is_completed' => false,
        ]);
    }

    public function test_deadlines_page_shows_stage_terms_and_deadline_reminders_grouped_by_urgency(): void
    {
        $this->runningStep('Contestación de la demanda', 2);
        $this->runningStep('Alegatos de conclusión', 15);
        $this->reminder('Término para recurso', 'vencimiento', -3);
        $this->reminder('Audiencia inicial', 'audiencia', 2);
        $this->actingAs($this->admin);

        Livewire::test(DeadlinesDashboard::class)
            ->assertSeeInOrder(['Vencidos', 'Término para recurso', 'Próximos 3 días hábiles', 'Contestación de la demanda', 'En 2 días hábiles', 'Más adelante', 'Alegatos de conclusión'])
            ->assertSee('LW-0001-2026')
            // Las audiencias son de la Agenda, no de Vencimientos.
            ->assertDontSee('Audiencia inicial');
    }

    public function test_deadlines_page_explains_how_terms_appear_when_empty(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(DeadlinesDashboard::class)
            ->assertSee('No hay términos corriendo en los próximos 30 días.')
            ->assertSee('Etapas y términos');
    }

    public function test_deadlines_page_warns_when_processes_could_not_be_consulted(): void
    {
        $this->case->forceFill(['external_case_number' => '11001310300120260012300', 'sync_failed_at' => now(), 'sync_error' => 'HTTP 503'])->save();
        $this->actingAs($this->admin);

        Livewire::test(DeadlinesDashboard::class)
            ->assertSee('1 proceso no se ha podido consultar recientemente.')
            ->assertSee('Ver casos con error de consulta');
    }

    public function test_collaborator_only_sees_terms_of_cases_with_flow_permission(): void
    {
        $other = LegalCase::factory()->create([
            'firm_id' => $this->firm->id,
            'user_id' => $this->admin->id,
            'client_id' => $this->case->client_id,
            'is_demo' => false,
            'status' => 'en_progreso',
        ]);
        $this->runningStep('Etapa permitida', 5);
        $this->runningStep('Etapa ajena', 5, $other);

        $collaborator = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'abogado']);
        CasePermission::create(['user_id' => $collaborator->id, 'legal_case_id' => $this->case->id, 'permissions' => ['case.view', 'flow.view'], 'assigned_by' => $this->admin->id]);
        $this->actingAs($collaborator);

        $titles = app(CasePendingSummary::class)->deadlinesFor($collaborator)->pluck('title')->all();

        $this->assertSame(['Etapa permitida'], $titles);
    }

    public function test_agenda_lists_reminders_with_relative_dates_and_no_duplicate_deadlines_tab(): void
    {
        $this->reminder('Audiencia inicial', 'audiencia', 2);
        $overdue = $this->reminder('Llamar al cliente', 'tarea', -3);
        $this->actingAs($this->admin);

        Livewire::test(ListReminders::class)
            ->assertSee('Los términos de sus procesos están en Vencimientos.')
            ->assertSee('Audiencia inicial')
            ->assertSee('En 2 días')
            ->assertSee('Vencido hace 3 días')
            ->assertSee('LW-0001-2026')
            ->assertDontSee('Acerca de los recordatorios')
            ->assertTableColumnStateSet('priority', null, $overdue);
    }
}
