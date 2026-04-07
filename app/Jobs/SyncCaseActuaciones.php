<?php

namespace App\Jobs;

use App\Models\CaseEvent;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Models\TybaSyncLog;
use App\Notifications\TybaSyncNotification;
use App\Services\TybaService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncCaseActuaciones implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 2;

    public function __construct(
        public LegalCase $case,
        public string $origen = 'automatico',
    ) {}

    public function handle(TybaService $tyba): void
    {
        $radicado = $this->case->external_case_number;

        if (! $radicado) {
            return;
        }

        $info = $tyba->extractProcessInfo($radicado);

        if (! $info) {
            Log::warning('Tyba sync: consulta fallida', ['case' => $this->case->id]);
            $this->case->update(['last_tyba_sync' => now()]);

            TybaSyncLog::create([
                'legal_case_id' => $this->case->id,
                'status' => 'error',
                'mensaje' => 'No se pudo consultar el radicado en la Rama Judicial',
                'origen' => $this->origen,
            ]);

            return;
        }

        // Actualizar datos del caso
        $updates = [
            'last_tyba_sync' => now(),
            'tyba_data' => collect($info)->except(['sujetos', 'actuaciones'])->toArray(),
        ];

        if (! empty($info['despacho'])) {
            $updates['court'] = $info['despacho'];
        }
        if (! empty($info['ponente'])) {
            $updates['judge'] = mb_convert_case(mb_strtolower($info['ponente']), MB_CASE_TITLE, 'UTF-8');
        }

        $this->case->update($updates);

        // Registrar actuaciones nuevas
        $newCount = 0;

        foreach ($info['actuaciones'] as $a) {
            $date = $this->parseDate($a['fecha']);

            if (! $date) {
                continue;
            }

            $title = $a['tipo'].($a['ciclo'] ? " ({$a['ciclo']})" : '');

            $exists = CaseEvent::where('legal_case_id', $this->case->id)
                ->where('event_date', $date)
                ->where('title', $title)
                ->exists();

            if ($exists) {
                continue;
            }

            CaseEvent::create([
                'legal_case_id' => $this->case->id,
                'title' => $title,
                'event_date' => $date,
                'event_type' => 'actuacion',
                'description' => ($a['anotacion'] ?? '').($a['anotacion'] ? ' | ' : '')."Sincronizado. Radicado: {$radicado}",
                'user_id' => $this->case->user_id,
            ]);

            $newCount++;

            // Crear recordatorio automatico para actuaciones con fechas futuras
            $this->createReminderIfNeeded($a, $date);
        }

        // Registrar log de sincronizacion
        TybaSyncLog::create([
            'legal_case_id' => $this->case->id,
            'status' => $newCount > 0 ? 'ok' : 'sin_cambios',
            'nuevas_actuaciones' => $newCount,
            'mensaje' => $newCount > 0
                ? "{$newCount} nueva(s) actuacion(es) de ".count($info['actuaciones']).' totales'
                : 'Sin novedades. '.count($info['actuaciones']).' actuaciones verificadas',
            'origen' => $this->origen,
        ]);

        Log::info('Tyba sync completado', [
            'case' => $this->case->case_number,
            'nuevas' => $newCount,
        ]);

        // Notificar al abogado si hay nuevas actuaciones
        if ($newCount > 0 && $this->case->user) {
            $this->case->user->notify(new TybaSyncNotification(
                $this->case,
                $newCount,
            ));
        }
    }

    /**
     * Crear recordatorio para actuaciones relevantes con fechas futuras.
     *
     * @param  array<string, mixed>  $actuacion
     */
    private function createReminderIfNeeded(array $actuacion, Carbon $date): void
    {
        // Solo crear recordatorios para fechas futuras o recientes (ultimos 3 dias)
        if ($date->lt(now()->subDays(3))) {
            return;
        }

        $tipo = strtolower($actuacion['tipo']);

        // Tipos de actuaciones que merecen recordatorio
        $esImportante = str_contains($tipo, 'auto') ||
            str_contains($tipo, 'audiencia') ||
            str_contains($tipo, 'sentencia') ||
            str_contains($tipo, 'fija fecha') ||
            str_contains($tipo, 'traslado') ||
            str_contains($tipo, 'notificacion');

        if (! $esImportante) {
            return;
        }

        // Verificar que no exista ya
        $exists = Reminder::where('legal_case_id', $this->case->id)
            ->where('due_date', $date)
            ->where('title', 'like', '%'.$actuacion['tipo'].'%')
            ->exists();

        if ($exists) {
            return;
        }

        $reminderType = str_contains($tipo, 'audiencia') ? 'audiencia' : 'vencimiento';
        $priority = str_contains($tipo, 'sentencia') || str_contains($tipo, 'audiencia') ? 'alta' : 'media';

        Reminder::create([
            'firm_id' => $this->case->firm_id,
            'user_id' => $this->case->user_id,
            'legal_case_id' => $this->case->id,
            'title' => $actuacion['tipo'].' - '.$this->case->case_number,
            'description' => "Actuacion detectada automaticamente desde Rama Judicial.\nRadicado: {$this->case->external_case_number}",
            'type' => $reminderType,
            'due_date' => $date,
            'remind_at' => $date->copy()->subDay(),
            'is_completed' => false,
            'priority' => $priority,
        ]);
    }

    private function parseDate(string $date): ?Carbon
    {
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, trim($date));
            } catch (\Exception) {
                continue;
            }
        }

        return null;
    }
}
