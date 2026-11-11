<?php

namespace Tests\Feature;

use App\Models\CaseEvent;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Models\TybaSyncLog;
use App\Models\User;
use App\Notifications\TybaSyncNotification;
use App\Services\CaseSyncService;
use App\Services\TybaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CaseSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 10:00:00');
        Notification::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_registers_new_actuaciones_and_logs_them_as_changes(): void
    {
        $case = $this->makeCase();
        $this->fakeTyba([$this->actuacion('Auto admite demanda', '05/11/2026')]);

        $log = app(CaseSyncService::class)->sync($case);

        $this->assertSame('ok', $log->status);
        $this->assertSame(1, $log->nuevas_actuaciones);
        $this->assertSame('Auto admite demanda', $log->cambios['actuaciones'][0]['tipo']);
        $this->assertSame(1, CaseEvent::where('legal_case_id', $case->id)->count());
        Notification::assertSentTo($case->user, TybaSyncNotification::class);
    }

    public function test_resyncing_does_not_duplicate_actuaciones(): void
    {
        $case = $this->makeCase();
        $this->fakeTyba([$this->actuacion('Auto admite demanda', '05/11/2026')]);

        app(CaseSyncService::class)->sync($case);
        $second = app(CaseSyncService::class)->sync($case->fresh());

        $this->assertSame('sin_cambios', $second->status);
        $this->assertNull($second->cambios);
        $this->assertSame(1, CaseEvent::where('legal_case_id', $case->id)->count());
    }

    public function test_it_detects_changes_in_process_data_between_syncs(): void
    {
        $case = $this->makeCase(['tyba_data' => ['despacho' => 'Juzgado 01 Civil Municipal', 'ponente' => 'Ana Perez']]);
        $this->fakeTyba([], ['despacho' => 'Juzgado 05 Civil del Circuito', 'ponente' => 'Ana Perez']);

        $log = app(CaseSyncService::class)->sync($case);

        $this->assertSame('ok', $log->status);
        $this->assertSame([
            ['campo' => 'Despacho', 'antes' => 'Juzgado 01 Civil Municipal', 'despues' => 'Juzgado 05 Civil del Circuito'],
        ], $log->cambios['campos']);
        $this->assertSame('Juzgado 05 Civil del Circuito', $case->fresh()->court);
    }

    public function test_first_sync_does_not_report_field_changes(): void
    {
        $case = $this->makeCase(['tyba_data' => null]);
        $this->fakeTyba([]);

        $log = app(CaseSyncService::class)->sync($case);

        $this->assertSame('sin_cambios', $log->status);
        Notification::assertNothingSent();
    }

    public function test_failed_query_creates_error_log(): void
    {
        $case = $this->makeCase();
        $this->mock(TybaService::class, fn (MockInterface $mock) => $mock->shouldReceive('extractProcessInfo')->andReturn(null));

        $log = app(CaseSyncService::class)->sync($case);

        $this->assertSame('error', $log->status);
        $this->assertSame(1, TybaSyncLog::count());
    }

    public function test_it_creates_a_hearing_reminder_from_the_date_in_the_annotation(): void
    {
        $case = $this->makeCase();
        $this->fakeTyba([
            $this->actuacion('Auto fija fecha audiencia', '05/11/2026', 'Se fija fecha para audiencia inicial el 20 de noviembre de 2026 a las 9:30 a.m.'),
        ]);

        app(CaseSyncService::class)->sync($case);

        $reminder = Reminder::where('legal_case_id', $case->id)->sole();
        $this->assertSame('audiencia', $reminder->type);
        $this->assertSame('2026-11-20 09:30', $reminder->due_date->format('Y-m-d H:i'));
        $this->assertSame('2026-11-19 08:00', $reminder->remind_at->format('Y-m-d H:i'));
    }

    public function test_it_does_not_overwrite_a_description_written_by_the_lawyer(): void
    {
        $case = $this->makeCase(['description' => 'Notas privadas del abogado']);
        $this->fakeTyba([]);

        app(CaseSyncService::class)->sync($case);

        $this->assertSame('Notas privadas del abogado', $case->fresh()->description);
    }

    /**
     * @return array<int, array{0: string, 1: string|null}>
     */
    public static function scheduledDateProvider(): array
    {
        return [
            'mes en letras con hora pm' => ['Audiencia el 3 de diciembre de 2026 a las 2:00 p.m.', '2026-12-03 14:00'],
            'formato numerico' => ['Se señala el 15/01/2027 hora 10:15 para diligencia', '2027-01-15 10:15'],
            'sin hora' => ['Diligencia programada para el 2026-12-01', '2026-12-01 08:00'],
            'fecha pasada' => ['Audiencia celebrada el 1 de enero de 2026', null],
            'sin fecha' => ['Se fija fecha de audiencia mediante auto posterior', null],
            'fecha invalida' => ['Audiencia el 31/02/2027', null],
        ];
    }

    #[DataProvider('scheduledDateProvider')]
    public function test_extract_scheduled_date(string $text, ?string $expected): void
    {
        $date = app(CaseSyncService::class)->extractScheduledDate($text, Carbon::parse('2026-11-05'));

        $this->assertSame($expected, $date?->format('Y-m-d H:i'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeCase(array $attributes = []): LegalCase
    {
        $firm = Firm::factory()->create();

        return LegalCase::factory()->create(array_merge([
            'firm_id' => $firm->id,
            'user_id' => User::factory()->create(['firm_id' => $firm->id])->id,
            'external_case_number' => '11001400300120260012300',
            'status' => 'abierto',
            'description' => null,
            'tyba_data' => ['despacho' => 'Juzgado 01 Civil Municipal'],
        ], $attributes));
    }

    /**
     * @param  array<int, array<string, mixed>>  $actuaciones
     * @param  array<string, mixed>  $overrides
     */
    private function fakeTyba(array $actuaciones, array $overrides = []): void
    {
        $info = array_merge([
            'tipo_proceso' => 'Verbal',
            'clase_proceso' => 'Declarativo',
            'subclase' => '',
            'departamento' => 'Bogota',
            'despacho' => 'Juzgado 01 Civil Municipal',
            'ponente' => '',
            'ubicacion' => '',
            'recurso' => '',
            'sujetos' => [['rol' => 'Demandado', 'nombre' => 'Empresa XYZ', 'documento' => '']],
            'actuaciones' => $actuaciones,
        ], $overrides);

        $this->mock(TybaService::class, fn (MockInterface $mock) => $mock->shouldReceive('extractProcessInfo')->andReturn($info));
    }

    /**
     * @return array<string, mixed>
     */
    private function actuacion(string $tipo, string $fecha, string $anotacion = ''): array
    {
        return [
            'ciclo' => '',
            'tipo' => $tipo,
            'fecha' => $fecha,
            'fecha_registro' => $fecha,
            'fecha_inicial' => '',
            'fecha_final' => '',
            'anotacion' => $anotacion,
            'cod_regla' => '',
            'con_documentos' => false,
        ];
    }
}
