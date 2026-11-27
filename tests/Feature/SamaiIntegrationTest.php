<?php

namespace Tests\Feature;

use App\Filament\Pages\BuscarProcesosTyba;
use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\LegalCases\Pages\ListLegalCases;
use App\Models\CaseEvent;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\CaseSyncService;
use App\Services\SamaiService;
use App\Services\TybaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class SamaiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const RADICADO = '11001333501820260048800';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_find_by_radicado_uses_the_corporation_from_the_radicado(): void
    {
        $this->fakeSamai([$this->samaiItem()]);

        $item = app(SamaiService::class)->findByRadicado(self::RADICADO);

        $this->assertSame(self::RADICADO, $item['radicado']);
        $this->assertSame('Juzgado Administrativo de Bogotá', $item['corporacion_nombre']);
        $this->assertSame('Vigente', $item['vigente']);
        $this->assertStringContainsString('list_procesos.aspx?guid=', $item['url']);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/buscar')
            && $request['tipoBusqueda'] === 'radicado'
            && $request['corporacion'] === '1100133'
            && $request['criterio'] === self::RADICADO);
    }

    public function test_find_by_radicado_returns_null_when_samai_fails(): void
    {
        Http::fake(['samai.consejodeestado.gov.co/*' => Http::response(['ok' => false, 'error' => ['message' => 'x']], 200)]);

        $this->assertNull(app(SamaiService::class)->findByRadicado(self::RADICADO));
        $this->assertNull(app(SamaiService::class)->findByRadicado('123'));
    }

    public function test_search_by_party_reads_several_pages(): void
    {
        $page = array_fill(0, 10, $this->samaiItem());
        Http::fake([
            '*/corporaciones' => Http::response(['ok' => true, 'data' => [['value' => '1100133', 'text' => 'Juzgado Administrativo de Bogotá']]]),
            '*/buscar' => Http::sequence()
                ->push(['ok' => true, 'data' => ['items' => $page]])
                ->push(['ok' => true, 'data' => ['items' => [$this->samaiItem()]]]),
        ]);

        $items = app(SamaiService::class)->searchByParty('COLPENSIONES', '1100133', 'demandado');

        $this->assertCount(11, $items);
    }

    public function test_samai_sync_detects_archived_process(): void
    {
        $case = $this->samaiCase(['vigente' => 'Vigente', 'ponente' => 'ANA PEREZ']);
        $this->fakeSamai([$this->samaiItem(['vigente' => 'NO', 'ponente' => 'LUIS GOMEZ'])]);

        $log = app(CaseSyncService::class)->sync($case);

        $this->assertSame('ok', $log->status);
        $this->assertEqualsCanonicalizing(['Ponente', 'Estado en SAMAI'], array_column($log->cambios['campos'], 'campo'));
        $this->assertSame(1, CaseEvent::where('legal_case_id', $case->id)->where('title', 'Proceso archivado en SAMAI')->count());
        $this->assertSame('Luis Gomez', $case->fresh()->judge);

        $second = app(CaseSyncService::class)->sync($case->fresh());
        $this->assertSame('sin_cambios', $second->status);
    }

    public function test_samai_sync_does_not_call_rama_judicial(): void
    {
        $case = $this->samaiCase([]);
        $this->fakeSamai([$this->samaiItem()]);
        $this->mock(TybaService::class, fn (MockInterface $mock) => $mock->shouldNotReceive('extractProcessInfo'));

        $this->assertSame('sin_cambios', app(CaseSyncService::class)->sync($case)->status);
    }

    public function test_import_falls_back_to_samai_when_rama_judicial_has_no_process(): void
    {
        [$user, $client] = $this->firmUserAndClient();
        $this->mock(TybaService::class, fn (MockInterface $mock) => $mock->shouldReceive('extractProcessInfo')->andReturn(null));
        $this->fakeSamai([$this->samaiItem()]);

        $this->actingAs($user);

        Livewire::test(ListLegalCases::class)
            ->callAction('import_tyba', data: ['radicado' => self::RADICADO, 'client_id' => $client->id, 'user_id' => $user->id])
            ->assertHasNoActionErrors();

        $case = LegalCase::withoutGlobalScopes()->where('external_case_number', self::RADICADO)->sole();
        $this->assertTrue($case->isSamai());
        $this->assertSame('Administrativo', $case->caseType->name);
        $this->assertSame('COLPENSIONES', $case->opposing_party);
        $this->assertStringContainsString('Juzgado 18 Administrativo', $case->court);
        $this->assertMatchesRegularExpression('/^LW-\d{4}-\d{4}$/', $case->case_number);
    }

    public function test_search_page_finds_processes_in_samai(): void
    {
        [$user] = $this->firmUserAndClient();
        $this->fakeSamai([$this->samaiItem()]);

        $this->actingAs($user);

        Livewire::test(BuscarProcesosTyba::class)
            ->fillForm(['fuente' => 'samai', 'modo' => 'nombre', 'nombre' => 'COLPENSIONES', 'corporacion' => '1100133'])
            ->call('buscar')
            ->assertSet('searchedSource', 'samai')
            ->assertSee(self::RADICADO)
            ->assertSee('Ver en SAMAI');
    }

    public function test_search_detects_name_document_or_radicado_in_one_field(): void
    {
        $this->assertSame(['modo' => 'radicado', 'termino' => '11001310300120240015800', 'tipo_documento' => null], BuscarProcesosTyba::detectarTipoBusqueda(' 11001-31-03-001-2024-00158-00 '));
        $this->assertSame(['modo' => 'documento', 'termino' => '1098765432', 'tipo_documento' => 1], BuscarProcesosTyba::detectarTipoBusqueda('1.098.765.432'));
        $this->assertSame(['modo' => 'documento', 'termino' => '900123456', 'tipo_documento' => 3], BuscarProcesosTyba::detectarTipoBusqueda('900.123.456'));
        $this->assertSame(['modo' => 'nombre', 'termino' => 'GARCIA LOPEZ MARIA', 'tipo_documento' => null], BuscarProcesosTyba::detectarTipoBusqueda('GARCIA  LOPEZ MARIA'));
    }

    public function test_search_in_all_sources_merges_rama_and_samai_by_radicado(): void
    {
        [$user] = $this->firmUserAndClient();
        $this->mock(TybaService::class, fn (MockInterface $mock) => $mock->shouldReceive('extractProcessInfo')->once()->andReturn([
            'codigo_proceso' => '11001310300120240015800',
            'despacho' => 'Juzgado 1 Civil del Circuito',
            'departamento' => 'BOGOTA',
        ]));
        $this->fakeSamai([$this->samaiItem()]);

        $this->actingAs($user);

        // Un radicado distinto en cada fuente: los dos aparecen, cada uno con su fuente.
        Livewire::test(BuscarProcesosTyba::class)
            ->assertSet('data.fuente', 'todas')
            ->assertSet('data.modo', 'auto')
            ->fillForm(['termino' => self::RADICADO])
            ->call('buscar')
            ->assertSet('searchedSource', 'todas')
            ->assertSet('searchedMode', 'radicado')
            ->assertSee('11001310300120240015800')
            ->assertSee(self::RADICADO)
            ->assertSee('SAMAI')
            ->assertSet('avisos', []);
    }

    public function test_document_search_in_all_sources_uses_rama_and_explains_samai(): void
    {
        [$user] = $this->firmUserAndClient();
        $this->mock(TybaService::class, fn (MockInterface $mock) => $mock->shouldReceive('documentSearchUnavailable')->andReturn(false)->getMock()->shouldReceive('searchClientProcesses')->once()->with('1098765432', 1, '')->andReturn([
            ['radicado' => '11001310300120240015800', 'despacho' => 'Juzgado 1 Civil', 'departamento' => 'BOGOTA', 'fecha' => '', 'ultima_actuacion' => '', 'sujetos' => '', 'es_privado' => false],
        ]));
        Http::fake();

        $this->actingAs($user);

        Livewire::test(BuscarProcesosTyba::class)
            ->fillForm(['termino' => '1098765432'])
            ->call('buscar')
            ->assertSet('searchedMode', 'documento')
            ->assertSee('11001310300120240015800')
            ->assertSee('SAMAI no permite buscar por cédula');

        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/buscar'));
    }

    public function test_rama_without_document_search_falls_back_to_the_client_name(): void
    {
        Http::fake([
            '*/DocumentoIdentificacion*' => Http::response(['StatusCode' => 404, 'Message' => 'Tipo de consulta: DocumentoIdentificacion, no existe.'], 404),
            '*/NombreRazonSocial*' => Http::response(['procesos' => [
                ['llaveProceso' => '11001310300120240015800', 'despacho' => 'Juzgado 1 Civil', 'departamento' => 'BOGOTA', 'sujetosProcesales' => 'Demandante: CARLOS ANDRES PEREZ GOMEZ | Demandado: BANCO'],
                ['llaveProceso' => '11001310300120240099900', 'despacho' => 'Juzgado 2 Civil', 'departamento' => 'BOGOTA', 'sujetosProcesales' => 'Demandante: CARLOS PEREZ RUIZ'],
            ], 'paginacion' => ['cantidadPaginas' => 1]]),
        ]);

        $tyba = app(TybaService::class);
        $found = $tyba->searchClientProcesses('79845123', 1, 'Carlos Andrés Pérez Gómez');

        $this->assertTrue($tyba->documentSearchUnavailable());
        $this->assertSame(['11001310300120240015800'], array_column($found, 'radicado'), 'Solo los procesos donde aparece el nombre completo.');
    }

    public function test_document_of_a_non_client_asks_for_the_name_and_searches_without_registering(): void
    {
        [$user] = $this->firmUserAndClient();
        Http::fake([
            '*/DocumentoIdentificacion*' => Http::response(['StatusCode' => 404], 404),
            '*/NombreRazonSocial*' => Http::response(['procesos' => [
                ['llaveProceso' => '68001400300120250012300', 'despacho' => 'Juzgado 1 Civil', 'departamento' => 'SANTANDER', 'sujetosProcesales' => 'Demandante: LUISA FERNANDA ROJAS DIAZ | Demandado: BANCO'],
                ['llaveProceso' => '68001400300120250099900', 'despacho' => 'Juzgado 2 Civil', 'departamento' => 'SANTANDER', 'sujetosProcesales' => 'Demandante: LUISA ROJAS PINTO'],
            ], 'paginacion' => ['cantidadPaginas' => 1]]),
            '*/corporaciones' => Http::response(['ok' => true, 'data' => []]),
        ]);
        $this->actingAs($user);

        $page = Livewire::test(BuscarProcesosTyba::class)
            ->fillForm(['termino' => '1098631735'])
            ->call('buscar')
            ->assertSet('documentoSinCliente', ['numero' => '1098631735', 'tipo' => 1])
            ->assertSee('no es de ninguno de sus clientes')
            ->assertSee('Regístrelo con este documento')
            ->assertDontSee('proceso(s) encontrado(s)')
            ->call('buscarPorNombreDePersona')
            ->assertHasErrors(['nombresPersona', 'apellidosPersona'])
            ->set('nombresPersona', 'Luisa Fernanda')
            ->set('apellidosPersona', 'Rojas Díaz')
            ->call('buscarPorNombreDePersona')
            ->assertHasNoErrors()
            ->assertSee('68001400300120250012300')
            ->assertDontSee('68001400300120250099900')
            ->assertSee('Guardar como cliente');

        $this->assertFalse(Client::where('document_number', '1098631735')->exists(), 'Buscar no registra a nadie.');

        $page->call('guardarComoCliente')
            ->assertNotified('Cliente guardado')
            ->assertSet('documentoSinCliente', null);

        $client = Client::where('document_number', '1098631735')->sole();
        $this->assertSame(['CC', 'Luisa Fernanda', 'Rojas Díaz', $user->firm_id, $user->id], [$client->document_type, $client->first_name, $client->last_name, $client->firm_id, $client->user_id]);
    }

    public function test_register_link_prefills_the_client_form(): void
    {
        [$user] = $this->firmUserAndClient();
        $this->actingAs($user);

        Livewire::withQueryParams(['document_number' => '1098631735', 'document_type' => 'CC', 'first_name' => 'Luisa'])
            ->test(CreateClient::class)
            ->assertSet('data.document_number', '1098631735')
            ->assertSet('data.document_type', 'CC')
            ->assertSet('data.first_name', 'Luisa');
    }

    public function test_samai_corporations_failure_is_not_retried_on_every_request(): void
    {
        Http::fake(['*/corporaciones' => Http::response('', 500)]);

        $this->assertSame([], app(SamaiService::class)->corporations());
        $this->assertSame([], app(SamaiService::class)->corporations());

        Http::assertSentCount(1);
    }

    public function test_samai_case_view_renders(): void
    {
        $case = $this->samaiCase(['url' => 'https://samai.consejodeestado.gov.co/Vistas/Casos/list_procesos.aspx?guid=abc']);
        $admin = User::factory()->create(['firm_id' => $case->firm_id, 'role' => 'admin']);

        $this->actingAs($admin)
            ->get("/admin/legal-cases/{$case->id}")
            ->assertOk();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function fakeSamai(array $items): void
    {
        Http::fake([
            '*/corporaciones' => Http::response(['ok' => true, 'data' => [['value' => '1100133', 'text' => 'Juzgado Administrativo de Bogotá']]]),
            '*/buscar' => Http::response(['ok' => true, 'data' => ['items' => $items, 'total' => count($items)]]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function samaiItem(array $overrides = []): array
    {
        return array_merge([
            'radicado' => self::RADICADO,
            'corporacion' => '1100133',
            'guid' => self::RADICADO.'1100133',
            'ponente' => 'ANA PEREZ',
            'identidadDespacho' => ['officeName' => 'Juzgado 18 Administrativo Sección Segunda de Bogotá'],
            'clase' => 'NULIDAD Y RESTABLECIMIENTO DEL DERECHO',
            'fechaIngreso' => '2026-10-01',
            'vigente' => 'SI',
            'asunto' => 'RELIQUIDACION PENSIONAL',
            'demandante' => 'MARIA LOPEZ',
            'demandado' => 'COLPENSIONES',
            'esPrivado' => false,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $tybaData
     */
    private function samaiCase(array $tybaData): LegalCase
    {
        $firm = Firm::factory()->create();

        return LegalCase::factory()->create([
            'firm_id' => $firm->id,
            'user_id' => User::factory()->create(['firm_id' => $firm->id])->id,
            'external_case_number' => self::RADICADO,
            'judicial_source' => LegalCase::SOURCE_SAMAI,
            'status' => 'abierto',
            'tyba_data' => $tybaData ? array_merge(['radicado' => self::RADICADO, 'despacho' => 'Juzgado 18 Administrativo Sección Segunda de Bogotá', 'clase' => 'NULIDAD Y RESTABLECIMIENTO DEL DERECHO', 'demandante' => 'MARIA LOPEZ', 'demandado' => 'COLPENSIONES'], $tybaData) : null,
        ]);
    }

    /**
     * @return array{0: User, 1: Client}
     */
    private function firmUserAndClient(): array
    {
        $firm = Firm::factory()->create();
        $user = User::factory()->create(['firm_id' => $firm->id, 'role' => 'admin']);
        $client = Client::factory()->create(['firm_id' => $firm->id, 'user_id' => $user->id]);
        CaseType::firstOrCreate(['name' => 'Administrativo']);

        return [$user, $client];
    }
}
