<?php

namespace Tests\Feature;

use App\Filament\Pages\BuscarProcesosTyba;
use App\Models\Client;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\TybaService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Desde Buscar procesos se importan los procesos encontrados (uno, los marcados o todos)
 * sin repetir los que la firma ya tiene.
 */
class ProcessSearchImportTest extends TestCase
{
    use RefreshDatabase;

    private const PRIMERO = '68001400300120250012300';

    private const SEGUNDO = '68001400300120250045600';

    private const TERCERO = '68001400300120250078900';

    private Firm $firm;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->firm = Firm::factory()->create();
        $this->admin = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'admin']);

        Http::fake([
            '*/DocumentoIdentificacion*' => Http::response(['StatusCode' => 404], 404),
            '*/NombreRazonSocial*' => Http::response(['procesos' => collect([self::PRIMERO, self::SEGUNDO, self::TERCERO])
                ->map(fn (string $radicado): array => ['llaveProceso' => $radicado, 'despacho' => 'Juzgado Civil', 'departamento' => 'SANTANDER', 'sujetosProcesales' => 'Demandante: LUISA FERNANDA ROJAS DIAZ | Demandado: BANCO'])
                ->all(), 'paginacion' => ['cantidadPaginas' => 1]]),
            '*' => Http::response(['ok' => true, 'data' => []]),
        ]);

        // El detalle de cada radicado: datos, partes y dos actuaciones.
        $this->partialMock(TybaService::class, fn (MockInterface $mock) => $mock->shouldReceive('extractProcessInfo')->andReturnUsing(fn (string $radicado): array => [
            'codigo_proceso' => $radicado,
            'despacho' => 'JUZGADO 001 CIVIL MUNICIPAL DE BUCARAMANGA',
            'departamento' => 'SANTANDER',
            'tipo_proceso' => 'Declarativo',
            'clase_proceso' => 'Verbal',
            'especialidad' => 'Civil',
            'ponente' => '',
            'fecha_publicacion' => '05/03/2025',
            'sujetos' => [['rol' => 'Demandante', 'nombre' => 'LUISA FERNANDA ROJAS DIAZ'], ['rol' => 'Demandado', 'nombre' => 'BANCO']],
            'actuaciones' => [
                ['fecha' => '10/03/2025', 'tipo' => 'Radicación de proceso', 'ciclo' => ''],
                ['fecha' => '20/03/2025', 'tipo' => 'Auto admite demanda', 'ciclo' => ''],
            ],
        ]));

        $this->actingAs($this->admin);
    }

    public function test_document_of_a_registered_client_imports_a_case_in_their_name(): void
    {
        $client = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'first_name' => 'Luisa Fernanda', 'last_name' => 'Rojas Diaz', 'document_number' => '1098631735']);

        Livewire::test(BuscarProcesosTyba::class)
            ->fillForm(['termino' => '1098631735'])
            ->call('buscar')
            ->assertSet('clienteBuscadoId', $client->id)
            ->assertActionVisible('importar', ['modo' => 'uno', 'radicado' => self::PRIMERO])
            ->callAction('importar', arguments: ['modo' => 'uno', 'radicado' => self::PRIMERO])
            ->assertHasNoActionErrors()
            ->assertNotified('Caso importado a nombre de Luisa Fernanda Rojas Diaz')
            ->assertSet('existingRadicados', [self::PRIMERO]);

        $case = LegalCase::where('external_case_number', self::PRIMERO)->sole();
        $this->assertSame($client->id, $case->client_id);
        $this->assertSame(2, $case->events()->count(), 'Trae las actuaciones al importar.');
    }

    public function test_non_client_is_registered_and_all_new_processes_are_imported_without_duplicates(): void
    {
        $other = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id]);
        LegalCase::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'client_id' => $other->id, 'external_case_number' => self::SEGUNDO]);

        Livewire::test(BuscarProcesosTyba::class)
            ->fillForm(['termino' => '1098631735'])
            ->call('buscar')
            ->set('nombresPersona', 'Luisa Fernanda')
            ->set('apellidosPersona', 'Rojas Diaz')
            ->call('buscarPorNombreDePersona')
            ->assertSee('Registrar cliente e importar')
            ->assertSee('Importar todos los nuevos (2)')
            ->callAction('importar', arguments: ['modo' => 'todos'])
            ->assertHasNoActionErrors()
            ->assertNotified('2 casos importados a nombre de Luisa Fernanda Rojas Diaz')
            ->assertSet('documentoSinCliente', null);

        $client = Client::where('document_number', '1098631735')->sole();
        $this->assertSame(
            [self::PRIMERO, self::TERCERO],
            LegalCase::where('client_id', $client->id)->orderBy('external_case_number')->pluck('external_case_number')->all(),
        );
        $this->assertSame(1, LegalCase::where('external_case_number', self::SEGUNDO)->count(), 'El que ya existia no se duplica.');
    }

    public function test_name_search_imports_only_the_selected_processes_for_the_chosen_client(): void
    {
        $client = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id]);

        Livewire::test(BuscarProcesosTyba::class)
            ->fillForm(['termino' => 'Luisa Fernanda Rojas Diaz', 'fuente' => 'rama'])
            ->call('buscar')
            ->set('seleccionados', [self::PRIMERO, self::TERCERO])
            ->assertSee('Importar seleccionados (2)')
            ->callAction('importar', data: ['client_id' => $client->id], arguments: ['modo' => 'seleccionados'])
            ->assertHasNoActionErrors()
            ->assertSet('seleccionados', []);

        $this->assertSame(
            [self::PRIMERO, self::TERCERO],
            LegalCase::where('client_id', $client->id)->orderBy('external_case_number')->pluck('external_case_number')->all(),
        );
    }

    public function test_client_is_required_when_the_search_was_not_by_a_client_document(): void
    {
        Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id]);

        Livewire::test(BuscarProcesosTyba::class)
            ->fillForm(['termino' => 'Luisa Fernanda Rojas Diaz', 'fuente' => 'rama'])
            ->call('buscar')
            ->callAction('importar', data: ['destino' => 'existente', 'client_id' => null], arguments: ['modo' => 'uno', 'radicado' => self::PRIMERO])
            ->assertHasActionErrors(['client_id' => 'required']);

        $this->assertSame(0, LegalCase::count());
    }

    public function test_a_new_client_can_be_created_from_the_import_modal_with_the_searched_name(): void
    {
        Livewire::test(BuscarProcesosTyba::class)
            ->fillForm(['termino' => 'LUISA FERNANDA ROJAS DIAZ', 'fuente' => 'rama'])
            ->call('buscar')
            ->mountAction('importar', ['modo' => 'uno', 'radicado' => self::PRIMERO])
            ->assertActionDataSet(['destino' => 'nuevo', 'first_name' => 'Luisa Fernanda', 'last_name' => 'Rojas Diaz'])
            ->setActionData(['document_number' => '1098631735', 'email' => 'luisa@example.com'])
            ->callMountedAction()
            ->assertHasNoActionErrors()
            ->assertNotified('Caso importado a nombre de Luisa Fernanda Rojas Diaz');

        $client = Client::where('document_number', '1098631735')->sole();
        $this->assertSame(['CC', 'luisa@example.com', $this->firm->id], [$client->document_type, $client->email, $client->firm_id]);
        $this->assertSame($client->id, LegalCase::where('external_case_number', self::PRIMERO)->sole()->client_id);
    }

    public function test_new_client_document_is_required_and_reuses_an_existing_client(): void
    {
        $existing = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'document_number' => '555']);

        Livewire::test(BuscarProcesosTyba::class)
            ->fillForm(['termino' => 'Luisa Fernanda Rojas Diaz', 'fuente' => 'rama'])
            ->call('buscar')
            ->callAction('importar', data: ['destino' => 'nuevo', 'document_number' => ''], arguments: ['modo' => 'uno', 'radicado' => self::PRIMERO])
            ->assertHasActionErrors(['document_number' => 'required'])
            ->setActionData(['document_number' => '555'])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame(1, Client::count());
        $this->assertSame($existing->id, LegalCase::where('external_case_number', self::PRIMERO)->sole()->client_id);
    }

    public function test_already_imported_processes_have_no_import_button(): void
    {
        $client = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id]);
        LegalCase::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'client_id' => $client->id, 'external_case_number' => self::PRIMERO]);

        Livewire::test(BuscarProcesosTyba::class)
            ->fillForm(['termino' => 'Luisa Fernanda Rojas Diaz', 'fuente' => 'rama'])
            ->call('buscar')
            ->assertActionDisabled('importar', ['modo' => 'uno', 'radicado' => self::PRIMERO])
            ->assertSee('Importar todos los nuevos (2)');
    }
}
