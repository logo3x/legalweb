<?php

namespace Tests\Feature;

use App\Console\Commands\SyncTybaActuaciones;
use App\Filament\Pages\BuscarProcesosTyba;
use App\Jobs\SyncCaseActuaciones;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use App\Notifications\SyncFailuresNotification;
use App\Notifications\SystemNewsNotification;
use App\Services\CaseSyncService;
use App\Services\TybaService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Un fallo al consultar la Rama Judicial nunca debe verse como "sin novedades".
 */
class SyncFailureAlertsTest extends TestCase
{
    use RefreshDatabase;

    private const RADICADO = '11001400300120260012300';

    private Firm $firm;

    private User $lawyer;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->firm = Firm::factory()->create();
        $this->lawyer = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'abogado']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeCase(array $attributes = []): LegalCase
    {
        return LegalCase::factory()->create(array_merge([
            'firm_id' => $this->firm->id,
            'user_id' => $this->lawyer->id,
            'external_case_number' => self::RADICADO,
            'status' => 'abierto',
            'is_demo' => false,
            'tyba_data' => ['despacho' => 'Juzgado 01 Civil Municipal'],
            'last_tyba_sync' => now()->subDays(2),
        ], $attributes));
    }

    /**
     * Rama Judicial simulada: busqueda y detalle responden bien; las actuaciones segun $actuaciones.
     */
    private function fakeRama(mixed $actuaciones, int $actuacionesStatus = 200, array $search = ['idProceso' => 99, 'idConexion' => 1]): void
    {
        Http::fake([
            '*/Procesos/Consulta/NumeroRadicacion*' => Http::response(['procesos' => [array_merge(['despacho' => 'Juzgado 01 Civil Municipal'], $search)]]),
            '*/Proceso/Detalle/*' => Http::response(['despacho' => 'Juzgado 01 Civil Municipal', 'llaveProceso' => self::RADICADO]),
            '*/Proceso/Sujetos/*' => Http::response(['sujetos' => []]),
            '*/Proceso/Actuaciones/*' => Http::response($actuaciones, $actuacionesStatus),
        ]);
    }

    public function test_unreadable_actuaciones_are_an_error_not_no_news(): void
    {
        $case = $this->makeCase();
        $lastSync = $case->last_tyba_sync;
        $this->fakeRama(['error' => 'interno'], 500);

        $log = app(CaseSyncService::class)->sync($case);

        $this->assertSame('error', $log->status);
        $this->assertStringContainsString('actuaciones', $log->mensaje);
        $case->refresh();
        $this->assertTrue($case->last_tyba_sync->equalTo($lastSync), 'Un fallo no debe marcar el caso como sincronizado.');
        $this->assertNotNull($case->sync_failed_at);
        $this->assertSame(1, $case->sync_failures);
    }

    public function test_renamed_fields_in_the_response_are_detected(): void
    {
        $case = $this->makeCase();
        $this->fakeRama(['items' => [['actuacion' => 'Auto']]]);

        $log = app(CaseSyncService::class)->sync($case);

        $this->assertSame('error', $log->status);
        $this->assertStringContainsString('formato distinto', $log->mensaje);
    }

    public function test_search_without_process_id_does_not_throw(): void
    {
        $case = $this->makeCase();
        $this->fakeRama(['actuaciones' => []], 200, ['idConexion' => 1]);

        $log = app(CaseSyncService::class)->sync($case);

        $this->assertSame('error', $log->status);
        $this->assertStringContainsString('formato distinto', $log->mensaje);
    }

    public function test_a_successful_sync_clears_the_previous_error(): void
    {
        $case = $this->makeCase(['sync_failed_at' => now()->subHours(3), 'sync_error' => 'HTTP 503', 'sync_failures' => 2]);
        $this->fakeRama(['actuaciones' => [], 'paginacion' => ['cantidadPaginas' => 1]]);

        $log = app(CaseSyncService::class)->sync($case);

        $this->assertSame('sin_cambios', $log->status);
        $case->refresh();
        $this->assertNull($case->sync_failed_at);
        $this->assertSame(0, $case->sync_failures);
        $this->assertTrue($case->last_tyba_sync->isToday());
    }

    public function test_an_unexpected_exception_is_recorded_and_does_not_break_the_batch(): void
    {
        $case = $this->makeCase();
        $this->mock(TybaService::class, fn (MockInterface $mock) => $mock->shouldReceive('extractProcessInfo')->andThrow(new \RuntimeException('Undefined array key')));

        $log = app(CaseSyncService::class)->sync($case);

        $this->assertSame('error', $log->status);
        $this->assertNotNull($case->fresh()->sync_failed_at);
    }

    public function test_failed_cases_are_retried_every_hour(): void
    {
        Queue::fake();
        $due = $this->makeCase(['sync_failed_at' => now()->subHours(2), 'sync_error' => 'HTTP 503', 'sync_failures' => 1]);
        $this->makeCase(['external_case_number' => '11001400300120260099900', 'sync_failed_at' => now()->subMinutes(20), 'sync_failures' => 1]);
        $this->makeCase(['external_case_number' => '11001400300120260088800', 'sync_failed_at' => now()->subHours(2), 'sync_failures' => SyncTybaActuaciones::MAX_RETRIES]);

        $this->artisan('app:sync-tyba-actuaciones', ['--retry-failed' => true])->assertSuccessful();

        Queue::assertPushed(SyncCaseActuaciones::class, 1);
        Queue::assertPushed(SyncCaseActuaciones::class, fn (SyncCaseActuaciones $job): bool => $job->case->is($due) && $job->origen === 'automatico');
    }

    public function test_lawyer_gets_one_daily_notice_listing_the_failed_cases(): void
    {
        Cache::forever(SyncTybaActuaciones::LAST_RUN_KEY, now()->toDateTimeString());
        $this->makeCase(['sync_failed_at' => now()->subHours(5), 'sync_error' => 'La Rama Judicial no respondio (HTTP 503)']);
        $this->makeCase(['external_case_number' => '11001400300120260099900']);

        $this->artisan('app:notify-sync-failures')->assertSuccessful();
        $this->artisan('app:notify-sync-failures')->assertSuccessful();

        Notification::assertSentToTimes($this->lawyer, SyncFailuresNotification::class, 1);
        Notification::assertSentTo($this->lawyer, SyncFailuresNotification::class, function (SyncFailuresNotification $notification): bool {
            $mail = (string) $notification->toMail($this->lawyer)->render();

            return $notification->cases->count() === 1
                && str_contains($mail, self::RADICADO)
                && str_contains($mail, 'HTTP 503');
        });
    }

    public function test_superadmin_is_told_when_the_nightly_sync_did_not_run_or_fails_in_bulk(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin', 'firm_id' => null]);
        Cache::forever(SyncTybaActuaciones::LAST_RUN_KEY, now()->subDays(2)->toDateTimeString());

        foreach (range(1, 5) as $i) {
            $this->makeCase(['external_case_number' => '1100140030012026001230'.$i, 'sync_failed_at' => now()->subHours(4), 'sync_error' => 'HTTP 503']);
        }

        $this->artisan('app:notify-sync-failures')->assertSuccessful();

        Notification::assertSentTo($superadmin, SystemNewsNotification::class, fn (SystemNewsNotification $n): bool => $n->title === 'La sincronización nocturna no corrió');
        Notification::assertSentTo($superadmin, SystemNewsNotification::class, fn (SystemNewsNotification $n): bool => $n->title === 'Falla masiva al consultar procesos');
    }

    public function test_nightly_run_records_a_heartbeat(): void
    {
        Queue::fake();

        $this->artisan('app:sync-tyba-actuaciones')->assertSuccessful();

        $this->assertNotNull(Cache::get(SyncTybaActuaciones::LAST_RUN_KEY));
    }

    public function test_rama_searches_include_processes_without_recent_actuaciones(): void
    {
        // Por defecto la Rama solo devuelve procesos con actuaciones en los ultimos 30 dias.
        Http::fake(['*' => Http::response(['procesos' => []])]);
        $tyba = app(TybaService::class);

        $tyba->extractProcessInfo('68081310300120240001800');
        $tyba->searchByName('ANGIE VANESSA SOTO ARANGO');
        $tyba->searchByDocument('1098765432');

        foreach (['NumeroRadicacion', 'NombreRazonSocial', 'DocumentoIdentificacion'] as $endpoint) {
            Http::assertSent(fn (Request $request): bool => str_contains($request->url(), "/Procesos/Consulta/{$endpoint}")
                && ($request->data()['SoloActivos'] ?? null) === 'false');
        }
    }

    public function test_name_search_keeps_the_first_page_when_a_later_page_fails(): void
    {
        Http::fake(function (Request $request) {
            if (($request->data()['pagina'] ?? 1) > 1) {
                throw new ConnectionException('cURL error 28: Operation timed out');
            }

            return Http::response([
                'procesos' => [['llaveProceso' => self::RADICADO, 'despacho' => 'JUZGADO 1 CIVIL', 'sujetosProcesales' => 'Demandante: ANGIE SOTO']],
                'paginacion' => ['cantidadPaginas' => 3],
            ]);
        });

        $procesos = app(TybaService::class)->searchByName('ANGIE SOTO');

        $this->assertSame([self::RADICADO], array_column($procesos, 'radicado'));
    }

    public function test_search_page_loading_indicator_starts_hidden(): void
    {
        $user = User::factory()->create(['firm_id' => Firm::factory()->create()->id, 'role' => 'admin']);
        Filament::setCurrentPanel('admin');
        $this->actingAs($user);

        // Livewire no oculta por si mismo "wire:loading.delay.block": sin display:none se queda visible tras la busqueda.
        Livewire::test(BuscarProcesosTyba::class)
            ->assertSeeHtml('data-lw-loading style="display: none;"');
    }
}
