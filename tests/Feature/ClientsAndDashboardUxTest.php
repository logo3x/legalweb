<?php

namespace Tests\Feature;

use App\Filament\Pages\Reports;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Widgets\CasesByStatusChart;
use App\Filament\Widgets\CasesByTypeChart;
use App\Filament\Widgets\CasesOverview;
use App\Filament\Widgets\MyDay;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Models\User;
use App\Services\CasePendingSummary;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientsAndDashboardUxTest extends TestCase
{
    use RefreshDatabase;

    private Firm $firm;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->firm = Firm::factory()->create();
        $this->admin = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'admin']);
    }

    public function test_client_list_shows_name_document_contact_and_active_cases_in_few_columns(): void
    {
        $withEmail = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'first_name' => 'Angie', 'last_name' => 'Soto', 'document_type' => 'CC', 'document_number' => '1098765432', 'email' => 'angie@correo.co', 'is_demo' => false]);
        $withoutEmail = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'email' => null, 'is_demo' => false]);
        LegalCase::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'client_id' => $withEmail->id, 'status' => 'en_progreso']);
        LegalCase::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'client_id' => $withEmail->id, 'status' => 'cerrado']);
        $this->actingAs($this->admin);

        Livewire::test(ListClients::class)
            ->assertSee('Angie Soto')
            ->assertSee('CC 1098765432')
            ->assertTableColumnStateSet('active_cases_count', 1, $withEmail)
            ->assertSee('Sin correo')
            ->assertTableColumnHidden('user.name')
            ->searchTable('1098765432')
            ->assertCanSeeTableRecords([$withEmail])
            ->assertCanNotSeeTableRecords([$withoutEmail]);
    }

    public function test_dashboard_keeps_my_day_and_moves_firm_charts_to_reports(): void
    {
        $this->assertFalse(CasesByTypeChart::isDiscovered());
        $this->assertFalse(CasesByStatusChart::isDiscovered());

        $this->actingAs($this->admin);
        $this->assertContains(CasesByTypeChart::class, (fn () => $this->getHeaderWidgets())->call(new Reports));
        $this->assertTrue(CasesOverview::canView());

        $collaborator = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'abogado']);
        $this->actingAs($collaborator);
        $this->assertFalse(CasesOverview::canView(), 'Las cifras de toda la firma son solo para administradores.');
    }

    public function test_case_type_chart_merges_types_with_the_same_name(): void
    {
        $client = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id]);
        foreach (range(1, 3) as $i) {
            $type = CaseType::factory()->create(['name' => 'Civil']);
            LegalCase::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'client_id' => $client->id, 'case_type_id' => $type->id]);
        }
        $this->actingAs($this->admin);

        $options = (fn () => $this->getOptions())->call(new CasesByTypeChart);

        $this->assertSame(['Civil'], $options['labels']);
        $this->assertSame([3], $options['series']);
    }

    public function test_deadline_on_a_saturday_moves_to_the_next_business_day(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 27)->setTime(9, 0)); // viernes
        Reminder::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'type' => 'vencimiento', 'title' => 'Término del sábado', 'due_date' => now()->setDate(2026, 11, 28)->setTime(17, 0)]);
        $this->actingAs($this->admin);

        $item = app(CasePendingSummary::class)->deadlinesFor($this->admin)->sole();

        $this->assertSame('2026-11-30', $item['date']->toDateString(), 'Vence en día inhábil: pasa al lunes.');
        $this->assertSame(1, $item['remaining']);

        Livewire::test(MyDay::class)->assertSee('En 1 día(s) hábil(es)', false)->assertDontSee('Vence hoy');
    }
}
