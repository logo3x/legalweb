<?php

namespace Tests\Feature;

use App\Filament\Resources\LegalCases\Pages\ListLegalCases;
use App\Filament\Resources\LegalCases\Pages\ViewLegalCase;
use App\Filament\Resources\LegalCases\RelationManagers\PortalAccessLogsRelationManager;
use App\Models\CaseEvent;
use App\Models\CaseFlowProgress;
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

class CasesSectionUxTest extends TestCase
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
            'external_case_number' => '11001310300120260012300',
            'description' => "Importado desde Rama Judicial\nClase: PROCESOS VERBALES",
            'last_tyba_sync' => now()->subHours(3),
            'tyba_data' => ['codigo_proceso' => '11001310300120260012300', 'despacho' => 'JUZGADO 001 CIVIL DEL CIRCUITO DE BOGOTA'],
        ]);

        $this->actingAs($this->admin);
    }

    public function test_case_page_opens_with_what_is_pending(): void
    {
        $step = FlowStep::factory()->create(['name' => 'Contestación de la demanda', 'days_limit' => 3]);
        CaseFlowProgress::factory()->create([
            'legal_case_id' => $this->case->id,
            'flow_step_id' => $step->id,
            'status' => 'en_progreso',
            'started_at' => now(),
        ]);
        Reminder::factory()->create([
            'firm_id' => $this->firm->id,
            'user_id' => $this->admin->id,
            'legal_case_id' => $this->case->id,
            'title' => 'Audiencia inicial',
            'due_date' => now()->addDays(4),
            'is_completed' => false,
        ]);
        CaseEvent::factory()->create(['legal_case_id' => $this->case->id, 'title' => 'Auto admite demanda', 'event_date' => now()->subDays(2)]);

        Livewire::test(ViewLegalCase::class, ['record' => $this->case->getRouteKey()])
            ->assertSeeInOrder(['Lo pendiente', 'Próximo término', 'Contestación de la demanda', 'faltan 3 días hábiles'])
            ->assertSee('Audiencia inicial')
            ->assertSee('Auto admite demanda')
            ->assertSee('Al día')
            ->assertSee('Detalle importado')
            ->assertDontSee('Fecha de cierre');
    }

    public function test_failed_sync_is_shown_at_the_top_of_the_case(): void
    {
        $this->case->forceFill(['sync_failed_at' => now()->subHours(2), 'sync_error' => 'La Rama Judicial no respondio (HTTP 503)'])->save();

        Livewire::test(ViewLegalCase::class, ['record' => $this->case->getRouteKey()])
            ->assertSee('No pudimos consultarlo')
            ->assertSee('La Rama Judicial no respondio (HTTP 503)')
            ->assertSee('reintentamos cada hora');
    }

    public function test_case_without_pending_items_says_so(): void
    {
        $summary = app(CasePendingSummary::class);

        $this->assertNull($summary->nextDeadline($this->case));
        $this->assertNull($summary->nextReminder($this->case));
        $this->assertNull($summary->lastEvent($this->case));

        Livewire::test(ViewLegalCase::class, ['record' => $this->case->getRouteKey()])
            ->assertSee('Sin términos corriendo')
            ->assertSee('Nada agendado')
            ->assertSee('Aún sin actuaciones');
    }

    public function test_imported_description_stays_visible_without_rama_data(): void
    {
        $this->case->forceFill(['tyba_data' => null])->save();

        Livewire::test(ViewLegalCase::class, ['record' => $this->case->getRouteKey()])
            ->assertSee('Clase: PROCESOS VERBALES')
            ->assertDontSee('Detalle importado');
    }

    public function test_portal_tab_only_appears_when_the_case_uses_the_portal(): void
    {
        $this->assertFalse(PortalAccessLogsRelationManager::canViewForRecord($this->case, ViewLegalCase::class));

        $this->case->forceFill(['portal_enabled' => true])->save();

        $this->assertTrue(PortalAccessLogsRelationManager::canViewForRecord($this->case->fresh(), ViewLegalCase::class));
    }

    public function test_case_list_shows_sync_state_and_last_actuacion(): void
    {
        $failed = LegalCase::factory()->create([
            'firm_id' => $this->firm->id,
            'user_id' => $this->admin->id,
            'client_id' => $this->case->client_id,
            'is_demo' => false,
            'external_case_number' => '05001310500120260004500',
            'sync_failed_at' => now(),
            'sync_error' => 'Error de prueba',
        ]);
        CaseEvent::factory()->create(['legal_case_id' => $this->case->id, 'event_date' => now()->subDays(2)]);

        Livewire::test(ListLegalCases::class)
            ->assertCanSeeTableRecords([$this->case, $failed])
            ->assertTableColumnStateSet('sync_failed_at', 'Al día', $this->case)
            ->assertTableColumnStateSet('sync_failed_at', 'Con error', $failed)
            ->assertSee($this->case->external_case_number)
            ->assertTableColumnHidden('user.name');
    }

    public function test_lawyer_column_appears_when_the_firm_has_several_lawyers(): void
    {
        User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'abogado']);

        Livewire::test(ListLegalCases::class)->assertTableColumnVisible('user.name');
    }
}
