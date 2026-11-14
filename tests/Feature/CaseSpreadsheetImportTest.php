<?php

namespace Tests\Feature;

use App\Filament\Resources\LegalCases\Pages\ListLegalCases;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Firm;
use App\Models\ImportLog;
use App\Models\LegalCase;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\CaseSpreadsheetImporter;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class CaseSpreadsheetImportTest extends TestCase
{
    use RefreshDatabase;

    private Firm $firm;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Http::fake();

        $this->firm = Firm::factory()->create();
        $this->admin = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'admin']);
        $this->actingAs($this->admin);
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function xlsx(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'lwtest').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return $path;
    }

    private function csv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'lwtest').'.csv';
        file_put_contents($path, $content);

        return $path;
    }

    public function test_imports_cases_and_creates_missing_clients_from_excel(): void
    {
        $laboral = CaseType::create(['name' => 'Laboral']);
        $existing = Client::factory()->create(['firm_id' => $this->firm->id, 'document_number' => '52987456']);

        $path = $this->xlsx([
            ['Radicado', 'Título', 'Documento del cliente', 'Nombres del cliente', 'Apellidos del cliente', 'Despacho', 'Tipo de proceso', 'Estado', 'Prioridad', 'Fecha de inicio'],
            ['11001-31-03-001-2024-00123-00', 'Ejecutivo - Banco', '79845123', 'Carlos', 'Pérez', 'Juzgado 1 Civil', 'laboral', 'En trámite', 'ALTA', '15/03/2024'],
            ['', 'Tutela salud', '52987456', '', '', '', '', 'cerrado', '', ''],
            ['', '', '', '', '', '', '', '', '', ''],
        ]);

        $summary = (new CaseSpreadsheetImporter($this->admin))->import($path, 'xlsx');

        $this->assertSame(2, $summary['created']);
        $this->assertSame(1, $summary['clients_created']);

        $case = LegalCase::where('external_case_number', '11001310300120240012300')->firstOrFail();
        $this->assertSame($this->firm->id, $case->firm_id);
        $this->assertSame('en_progreso', $case->status);
        $this->assertSame('alta', $case->priority);
        $this->assertSame($laboral->id, $case->case_type_id);
        $this->assertSame('2024-03-15', $case->started_at->format('Y-m-d'));
        $this->assertNull($case->last_tyba_sync, 'La sincronizacion nocturna debe traer las actuaciones.');
        $this->assertSame('Carlos', $case->client->first_name);

        $closed = LegalCase::where('title', 'Tutela salud')->firstOrFail();
        $this->assertSame($existing->id, $closed->client_id);
        $this->assertSame('cerrado', $closed->status);
    }

    public function test_reads_csv_saved_by_spanish_excel_with_semicolons(): void
    {
        $path = $this->csv("\u{FEFF}Cedula;Nombre;Apellidos;Asunto;Juzgado\n1020456789;Jorge;Martínez;Divorcio;Juzgado 4 de Familia\n");

        $summary = (new CaseSpreadsheetImporter($this->admin))->import($path, 'csv');

        $this->assertSame(1, $summary['created']);
        $this->assertDatabaseHas('legal_cases', ['title' => 'Divorcio', 'court' => 'Juzgado 4 de Familia', 'firm_id' => $this->firm->id]);
        $this->assertDatabaseHas('clients', ['document_number' => '1020456789', 'first_name' => 'Jorge', 'firm_id' => $this->firm->id]);
    }

    public function test_reports_duplicates_and_rows_with_missing_data(): void
    {
        $client = Client::factory()->create(['firm_id' => $this->firm->id]);
        LegalCase::factory()->create(['firm_id' => $this->firm->id, 'client_id' => $client->id, 'external_case_number' => '11001310300120240012300']);

        $path = $this->csv("Radicado,Titulo,Documento del cliente,Nombres del cliente\n11001310300120240012300,Repetido,123,Ana\n,Sin documento,,Ana\n,Cliente sin nombre,999888,\n12345,Radicado corto,123,Ana\n");

        $summary = (new CaseSpreadsheetImporter($this->admin))->import($path, 'csv');

        $this->assertSame(0, $summary['created']);
        $this->assertSame(1, $summary['duplicates']);
        $this->assertSame(3, $summary['errors']);
        $this->assertSame(['duplicado', 'error', 'error', 'error'], array_column($summary['results'], 'status'));
        $this->assertStringContainsString('Fila 3', $summary['results'][1]['radicado']);
    }

    public function test_rejects_files_without_the_required_columns(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new CaseSpreadsheetImporter($this->admin))->import($this->csv("Nombre,Telefono\nAna,300\n"), 'csv');
    }

    public function test_never_reuses_clients_from_another_firm(): void
    {
        $otherFirm = Firm::factory()->create();
        $foreign = Client::factory()->create(['firm_id' => $otherFirm->id, 'document_number' => '79845123']);

        (new CaseSpreadsheetImporter($this->admin))->import($this->csv("Documento,Nombres,Titulo\n79845123,Carlos,Caso propio\n"), 'csv');

        $case = LegalCase::where('title', 'Caso propio')->firstOrFail();
        $this->assertNotSame($foreign->id, $case->client_id);
        $this->assertSame($this->firm->id, $case->client->firm_id);
    }

    public function test_respects_the_active_case_limit_of_the_free_plan(): void
    {
        $this->travelTo('2027-01-15 10:00:00');
        $free = Plan::create(['name' => 'Gratuito', 'slug' => 'gratuito', 'price_monthly' => 0, 'price_yearly' => 0, 'max_cases' => 5, 'max_users' => 1, 'ai_monthly_limit' => 10, 'has_whatsapp' => false, 'has_client_monitoring' => false, 'sync_every_days' => 7, 'is_active' => true]);
        Subscription::create(['firm_id' => $this->firm->id, 'plan_id' => $free->id, 'status' => 'active', 'starts_at' => now()->subMonths(4), 'trial_ends_at' => now()->subMonth()]);

        $lines = collect(range(1, 7))->map(fn (int $i): string => "10{$i},Cliente {$i},Caso {$i},abierto")->join("\n");
        $lines .= "\n200,Cliente cerrado,Caso archivado,archivado";

        $summary = (new CaseSpreadsheetImporter($this->admin))->import($this->csv("Documento,Nombres,Titulo,Estado\n".$lines."\n"), 'csv');

        $this->assertSame(6, $summary['created'], '5 activos del plan Gratuito mas el archivado.');
        $this->assertSame(2, collect($summary['results'])->where('status', 'limite')->count());
    }

    public function test_collaborators_cannot_assign_cases_to_other_lawyers(): void
    {
        $lawyer = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'abogado']);

        (new CaseSpreadsheetImporter($lawyer))->import($this->csv("Documento,Nombres,Titulo,Abogado responsable (correo)\n555,Ana,Caso ajeno,{$this->admin->email}\n"), 'csv');

        $this->assertSame($lawyer->id, LegalCase::where('title', 'Caso ajeno')->value('user_id'));
    }

    public function test_template_can_be_read_back_by_the_importer(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'lwtest').'.xlsx';
        CaseSpreadsheetImporter::writeTemplate($path);

        $summary = (new CaseSpreadsheetImporter($this->admin))->import($path, 'xlsx');

        $this->assertSame(1, $summary['created']);
        $this->assertDatabaseHas('legal_cases', ['external_case_number' => '11001310300120240012300', 'firm_id' => $this->firm->id]);
    }

    public function test_cases_list_still_searches_by_client_name(): void
    {
        $client = Client::factory()->create(['firm_id' => $this->firm->id, 'first_name' => 'Hermelinda']);
        $other = Client::factory()->create(['firm_id' => $this->firm->id, 'first_name' => 'Rodrigo']);
        $match = LegalCase::factory()->create(['firm_id' => $this->firm->id, 'client_id' => $client->id, 'user_id' => $this->admin->id]);
        $miss = LegalCase::factory()->create(['firm_id' => $this->firm->id, 'client_id' => $other->id, 'user_id' => $this->admin->id]);

        Livewire::test(ListLegalCases::class)
            ->searchTable('Hermelinda')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$miss]);
    }

    public function test_import_action_from_the_cases_page(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('procesos.csv', "Documento,Nombres,Titulo\n79845123,Carlos,Desde la pagina\n");

        Livewire::test(ListLegalCases::class)
            ->callAction('import_excel', ['file' => $file])
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('legal_cases', ['title' => 'Desde la pagina', 'firm_id' => $this->firm->id]);
        $this->assertSame(1, ImportLog::where('firm_id', $this->firm->id)->value('importados'));
        $this->assertSame([], Storage::disk('local')->allFiles('imports'), 'El archivo subido no debe quedar guardado.');
    }
}
