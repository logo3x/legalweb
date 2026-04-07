<?php

namespace App\Jobs;

use App\Models\CaseEvent;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Models\TybaSyncLog;
use App\Notifications\TybaSyncNotification;
use App\Services\JudicialCalendarService;
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
     * Crear recordatorios inteligentes para actuaciones relevantes.
     * Calcula plazos legales usando el calendario judicial colombiano.
     *
     * @param  array<string, mixed>  $actuacion
     */
    private function createReminderIfNeeded(array $actuacion, Carbon $date): void
    {
        $tipo = strtolower($actuacion['tipo']);

        // Mapa de actuaciones importantes con plazo legal en dias habiles
        $alertRules = $this->getAlertRules($tipo);

        if (! $alertRules) {
            return;
        }

        // Verificar que no exista ya
        $exists = Reminder::where('legal_case_id', $this->case->id)
            ->where('due_date', '>=', $date)
            ->where('title', 'like', '%'.$actuacion['tipo'].'%')
            ->exists();

        if ($exists) {
            return;
        }

        $calendar = app(JudicialCalendarService::class);

        // Calcular fecha de vencimiento del plazo
        $dueDate = $date;
        if ($alertRules['dias'] > 0) {
            $deadline = $calendar->calculateDeadline($date, $alertRules['dias'], 'business');
            $dueDate = $deadline['deadline'];
        }

        // Solo crear si la fecha de vencimiento es futura o reciente
        if ($dueDate->lt(now()->subDays(3))) {
            return;
        }

        $diasRestantes = (int) now()->diffInDays($dueDate, false);
        $priority = match (true) {
            $diasRestantes <= 1 => 'urgente',
            $diasRestantes <= 3 => 'alta',
            $diasRestantes <= 7 => 'media',
            default => 'baja',
        };

        // Sobreescribir prioridad para actuaciones criticas
        if ($alertRules['prioridad_minima'] === 'alta' && $priority === 'baja') {
            $priority = 'media';
        }
        if ($alertRules['prioridad_minima'] === 'urgente') {
            $priority = 'urgente';
        }

        $plazoTexto = $alertRules['dias'] > 0
            ? " (plazo: {$alertRules['dias']} dias habiles, vence {$dueDate->format('d/m/Y')})"
            : '';

        Reminder::create([
            'firm_id' => $this->case->firm_id,
            'user_id' => $this->case->user_id,
            'legal_case_id' => $this->case->id,
            'title' => $actuacion['tipo'].' - '.$this->case->case_number,
            'description' => "{$alertRules['descripcion']}{$plazoTexto}\n"
                ."Actuacion del {$date->format('d/m/Y')} detectada automaticamente.\n"
                ."Radicado: {$this->case->external_case_number}",
            'type' => $alertRules['tipo_recordatorio'],
            'due_date' => $dueDate,
            'remind_at' => $dueDate->copy()->subDay()->setHour(8),
            'is_completed' => false,
            'priority' => $priority,
        ]);
    }

    /**
     * Reglas de alerta segun tipo de actuacion.
     * Dias = plazo legal en dias habiles desde la actuacion.
     *
     * @return array{dias: int, tipo_recordatorio: string, prioridad_minima: string, descripcion: string}|null
     */
    private function getAlertRules(string $tipo): ?array
    {
        return match (true) {
            // Autos que requieren respuesta
            str_contains($tipo, 'auto admite') => [
                'dias' => 20, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'alta',
                'descripcion' => 'Auto admisorio de demanda. Plazo para notificar y/o contestar.',
            ],
            str_contains($tipo, 'auto requiere') => [
                'dias' => 5, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'alta',
                'descripcion' => 'Requerimiento del despacho. Debe atenderse dentro del plazo.',
            ],
            str_contains($tipo, 'auto decide') => [
                'dias' => 3, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'media',
                'descripcion' => 'Auto con decision. Revisar si procede recurso de reposicion (3 dias) o apelacion.',
            ],
            str_contains($tipo, 'auto ordena') => [
                'dias' => 5, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'media',
                'descripcion' => 'Auto que ordena una actuacion. Verificar cumplimiento.',
            ],
            str_contains($tipo, 'auto reconoce') => [
                'dias' => 0, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'media',
                'descripcion' => 'Auto que reconoce personeria o apoderado. Verificar contenido.',
            ],
            str_contains($tipo, 'fija fecha') || str_contains($tipo, 'auto fija') => [
                'dias' => 0, 'tipo_recordatorio' => 'audiencia', 'prioridad_minima' => 'alta',
                'descripcion' => 'Se fijo fecha para diligencia o audiencia. Verificar fecha y preparar.',
            ],

            // Audiencias y sentencias
            str_contains($tipo, 'audiencia') => [
                'dias' => 0, 'tipo_recordatorio' => 'audiencia', 'prioridad_minima' => 'urgente',
                'descripcion' => 'Audiencia programada. Preparar alegatos y pruebas.',
            ],
            str_contains($tipo, 'sentencia') => [
                'dias' => 10, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'urgente',
                'descripcion' => 'Sentencia proferida. Plazo para recurrir (apelacion).',
            ],

            // Traslados y notificaciones
            str_contains($tipo, 'traslado') => [
                'dias' => 3, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'alta',
                'descripcion' => 'Traslado corrido. Plazo para pronunciarse.',
            ],
            str_contains($tipo, 'fijacion estado') => [
                'dias' => 3, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'media',
                'descripcion' => 'Notificacion por estado. El termino empieza a correr al dia siguiente.',
            ],
            str_contains($tipo, 'notificacion') => [
                'dias' => 0, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'media',
                'descripcion' => 'Notificacion realizada. Verificar contenido y plazos.',
            ],

            default => null,
        };
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
