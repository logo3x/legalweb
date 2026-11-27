<?php

namespace Tests\Feature;

use App\Filament\Resources\LegalCases\Pages\ViewLegalCase;
use App\Filament\Resources\LegalCases\RelationManagers\EventsRelationManager;
use App\Models\CaseEvent;
use App\Models\Client;
use App\Models\Document;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\CaseImporter;
use App\Services\CaseSyncService;
use App\Services\TybaService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Las actuaciones importadas traen la anotacion y el aviso de documentos de la Rama, y sus
 * documentos se pueden ver, descargar y guardar en los documentos del caso.
 */
class RamaActuacionDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private const RADICADO = '68081400300120260062800';

    private User $admin;

    private LegalCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $firm = Firm::factory()->create();
        $this->admin = User::factory()->create(['firm_id' => $firm->id, 'role' => 'admin']);
        $client = Client::factory()->create(['firm_id' => $firm->id, 'user_id' => $this->admin->id]);
        $this->case = LegalCase::factory()->create(['firm_id' => $firm->id, 'user_id' => $this->admin->id, 'client_id' => $client->id]);
        $this->actingAs($this->admin);
    }

    /**
     * @return array<string, mixed>
     */
    private function processInfo(): array
    {
        return [
            'codigo_proceso' => self::RADICADO,
            'despacho' => 'JUZGADO 001 CIVIL MUNICIPAL DE BARRANCABERMEJA',
            'departamento' => 'SANTANDER',
            'tipo_proceso' => 'Declarativo',
            'clase_proceso' => 'Verbal Sumario',
            'especialidad' => 'Civil',
            'ponente' => '',
            'fecha_publicacion' => '18/08/2026',
            'sujetos' => [['rol' => 'Demandante', 'nombre' => 'LUIS GUILLERMO OVIEDO OCHOA']],
            'actuaciones' => [
                ['ref' => 'r:3989005141', 'ciclo' => '', 'tipo' => 'Auto Decide', 'fecha' => '15/10/2026', 'fecha_registro' => '15/10/2026', 'fecha_inicial' => '', 'fecha_final' => '', 'anotacion' => 'resuelve aclaracion', 'cod_regla' => '', 'con_documentos' => true],
                ['ref' => 'r:3989005151', 'ciclo' => '', 'tipo' => 'Fijacion Estado', 'fecha' => '16/10/2026', 'fecha_registro' => '15/10/2026', 'fecha_inicial' => '16/10/2026', 'fecha_final' => '16/10/2026', 'anotacion' => '', 'cod_regla' => '', 'con_documentos' => false],
            ],
        ];
    }

    public function test_imported_actuaciones_keep_the_annotation_the_rama_id_and_the_documents_flag(): void
    {
        $this->partialMock(TybaService::class, fn (MockInterface $mock) => $mock->shouldReceive('extractProcessInfo')->andReturn($this->processInfo()));

        $result = (new CaseImporter($this->admin))->import(self::RADICADO, $this->case->client_id, $this->admin->id);

        $auto = CaseEvent::where('legal_case_id', $result['case']->id)->where('title', 'Auto Decide')->sole();
        $this->assertStringContainsString('resuelve aclaracion', $auto->description);
        $this->assertStringContainsString('Tiene documentos anexos en la Rama Judicial', $auto->description);
        $this->assertTrue($auto->rama_documents);
        $this->assertSame('r:3989005141', $auto->rama_ref);
        $this->assertSame('00:00:00', $auto->event_date->format('H:i:s'));

        $estado = CaseEvent::where('legal_case_id', $result['case']->id)->where('title', 'Fijacion Estado')->sole();
        $this->assertFalse($estado->rama_documents);
        $this->assertStringNotContainsString('Importado desde Rama Judicial', (string) $estado->description);
    }

    public function test_next_sync_completes_actuaciones_imported_with_the_old_generic_description(): void
    {
        $this->case->update(['external_case_number' => self::RADICADO]);
        $old = CaseEvent::factory()->create([
            'legal_case_id' => $this->case->id,
            'title' => 'Auto Decide',
            'event_date' => '2026-10-15 15:29:16',
            'event_day' => '2026-10-15',
            'description' => 'Importado desde Rama Judicial. Radicado: '.self::RADICADO,
        ]);
        $this->partialMock(TybaService::class, fn (MockInterface $mock) => $mock->shouldReceive('extractProcessInfo')->andReturn($this->processInfo()));

        app(CaseSyncService::class)->sync($this->case, 'manual', false);

        $old->refresh();
        $this->assertStringContainsString('resuelve aclaracion', $old->description);
        $this->assertTrue($old->rama_documents);
        $this->assertSame('r:3989005141', $old->rama_ref);
    }

    public function test_lawyer_sees_downloads_and_saves_the_documents_of_an_actuacion(): void
    {
        Http::fake([
            '*/Proceso/DocumentosActuacion/3989005141' => Http::response([[
                'idRegDocumento' => 4032293321,
                'nombre' => '11AUTODECIDE.pdf',
                'descripcion' => 'Cargado el: 2026-10-15, tamaño: .21 KB',
                'tipo' => 'pdf',
                'fechaCarga' => '2026-10-15T00:00:00',
            ]]),
        ]);
        $event = CaseEvent::factory()->create(['legal_case_id' => $this->case->id, 'title' => 'Auto Decide', 'event_date' => '2026-10-15', 'rama_ref' => 'r:3989005141', 'rama_documents' => true]);
        $withoutDocuments = CaseEvent::factory()->create(['legal_case_id' => $this->case->id, 'title' => 'Fijacion Estado', 'event_date' => '2026-10-16', 'rama_ref' => 'r:1', 'rama_documents' => false]);

        $table = Livewire::test(EventsRelationManager::class, ['ownerRecord' => $this->case, 'pageClass' => ViewLegalCase::class])
            ->assertTableActionHidden('rama_documents', $withoutDocuments)
            ->assertTableActionVisible('rama_documents', $event)
            ->callTableAction('rama_documents', $event)
            ->assertNotified('Documento guardado');

        $document = Document::where('legal_case_id', $this->case->id)->sole();
        $this->assertSame([$event->id, 'https://consultaprocesos.ramajudicial.gov.co:448/api/v2/Descarga/Documento/4032293321', 'recibido', '2026-10-15'], [
            $document->case_event_id, $document->external_url, $document->status, $document->received_at->toDateString(),
        ]);

        $table->callTableAction('rama_documents', $event)->assertNotified('Ya estaban guardados');
        $this->assertSame(1, Document::count());
    }

    public function test_editing_an_actuacion_shows_its_rama_documents(): void
    {
        Http::fake([
            '*/Proceso/DocumentosActuacion/3989003451' => Http::response([[
                'idRegDocumento' => 4032290001,
                'nombre' => '10OFICIO.pdf',
                'descripcion' => 'Cargado el: 2026-10-15, tamaño: .30 KB',
                'tipo' => 'pdf',
                'fechaCarga' => '2026-10-15T00:00:00',
            ]]),
        ]);
        $event = CaseEvent::factory()->create(['legal_case_id' => $this->case->id, 'title' => 'Elaboración De Oficios Telegramas', 'event_date' => '2026-10-15', 'rama_ref' => 'r:3989003451', 'rama_documents' => true]);
        $manual = CaseEvent::factory()->create(['legal_case_id' => $this->case->id, 'title' => 'Llamada con el cliente', 'event_date' => '2026-10-15']);

        Livewire::test(EventsRelationManager::class, ['ownerRecord' => $this->case, 'pageClass' => ViewLegalCase::class])
            ->mountTableAction('edit', $event)
            ->assertSchemaComponentVisible('ramaDocuments');

        Livewire::test(EventsRelationManager::class, ['ownerRecord' => $this->case, 'pageClass' => ViewLegalCase::class])
            ->mountTableAction('edit', $manual)
            ->assertSchemaComponentHidden('ramaDocuments');

    }

    public function test_rama_outage_shows_a_message_instead_of_failing(): void
    {
        Http::fake(['*/Proceso/DocumentosActuacion/*' => Http::response('error', 500)]);
        $event = CaseEvent::factory()->create(['legal_case_id' => $this->case->id, 'title' => 'Auto Decide', 'event_date' => '2026-10-15', 'rama_ref' => 'r:3989005141', 'rama_documents' => true]);

        Livewire::test(EventsRelationManager::class, ['ownerRecord' => $this->case, 'pageClass' => ViewLegalCase::class])
            ->callTableAction('rama_documents', $event)
            ->assertNotified('No hay documentos para guardar');

        $this->assertSame(0, Document::count());

        $html = view('filament.modals.rama-documents', ['documentos' => [], 'error' => 'La Rama Judicial no respondió. Intente de nuevo en unos minutos.', 'guardados' => []])->render();
        $this->assertStringContainsString('La Rama Judicial no respondió', $html);
    }

    public function test_documents_modal_lists_each_file_with_its_download_link(): void
    {
        $url = app(TybaService::class)->documentDownloadUrl('4032293321');
        $html = view('filament.modals.rama-documents', [
            'documentos' => [['id' => '4032293321', 'nombre' => '11AUTODECIDE.pdf', 'descripcion' => 'Cargado el: 2026-10-15, tamaño: .21 KB', 'tipo' => 'pdf', 'fecha' => '15/10/2026', 'url' => $url]],
            'error' => null,
            'guardados' => [$url],
        ])->render();

        $this->assertStringContainsString('11AUTODECIDE.pdf', $html);
        $this->assertStringContainsString('href="https://consultaprocesos.ramajudicial.gov.co:448/api/v2/Descarga/Documento/4032293321"', $html);
        $this->assertStringContainsString('Ya está en los documentos del caso.', $html);
    }
}
