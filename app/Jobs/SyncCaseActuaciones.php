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
                'description' => $this->buildEventDescription($a, $radicado),
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
     * Crear recordatorios para actuaciones con plazo asociado.
     *
     * Fuentes de fecha (en orden de preferencia):
     *  1. fechaFinal devuelta por la API (la propia Rama Judicial calcula
     *     el termino para esa actuacion).
     *  2. Calculo via JudicialCalendarService cuando el tipo de actuacion
     *     coincide con una regla conocida (auto admite, traslado, etc.).
     *
     * @param  array<string, mixed>  $actuacion
     */
    private function createReminderIfNeeded(array $actuacion, Carbon $date): void
    {
        $tipo = strtolower($actuacion['tipo']);
        $alertRules = $this->getAlertRules($tipo);

        $dueDate = null;
        $fuenteFecha = '';

        // 1. La API entrega fechaFinal -> usarla directamente
        if (! empty($actuacion['fecha_final'])) {
            $dueDate = $this->parseDate($actuacion['fecha_final']);
            $fuenteFecha = 'Termino tomado de la Rama Judicial';
        }

        // 2. Calculo por regla legal conocida
        if (! $dueDate && $alertRules && $alertRules['dias'] > 0) {
            $deadline = app(JudicialCalendarService::class)
                ->calculateDeadline($date, $alertRules['dias'], 'business');
            $dueDate = $deadline['deadline'];
            $fuenteFecha = "Plazo de {$alertRules['dias']} dias habiles desde la actuacion";
        }

        // Sin fecha aprovechable: no creamos recordatorio
        if (! $dueDate) {
            return;
        }

        // Solo crear si la fecha de vencimiento es futura o reciente
        if ($dueDate->lt(now()->subDays(3))) {
            return;
        }

        // Evitar duplicados: misma actuacion + misma fecha de vencimiento
        $exists = Reminder::where('legal_case_id', $this->case->id)
            ->where('due_date', $dueDate)
            ->where('title', 'like', '%'.$actuacion['tipo'].'%')
            ->exists();

        if ($exists) {
            return;
        }

        $diasRestantes = (int) now()->diffInDays($dueDate, false);
        $priority = match (true) {
            $diasRestantes <= 1 => 'urgente',
            $diasRestantes <= 3 => 'alta',
            $diasRestantes <= 7 => 'media',
            default => 'baja',
        };

        // Sobrescribir prioridad cuando la regla la pide minima
        if ($alertRules) {
            if ($alertRules['prioridad_minima'] === 'alta' && $priority === 'baja') {
                $priority = 'media';
            }
            if ($alertRules['prioridad_minima'] === 'urgente') {
                $priority = 'urgente';
            }
        }

        $tipoRecordatorio = $alertRules['tipo_recordatorio'] ?? 'vencimiento';
        $descripcionBase = $alertRules['descripcion']
            ?? 'Termino judicial detectado en sincronizacion automatica.';

        Reminder::create([
            'firm_id' => $this->case->firm_id,
            'user_id' => $this->case->user_id,
            'legal_case_id' => $this->case->id,
            'title' => $actuacion['tipo'].' - '.$this->case->case_number,
            'description' => "{$descripcionBase}\n"
                ."{$fuenteFecha}.\n"
                ."Actuacion: {$date->format('d/m/Y')} | Vence: {$dueDate->format('d/m/Y')}\n"
                ."Radicado: {$this->case->external_case_number}",
            'type' => $tipoRecordatorio,
            'due_date' => $dueDate,
            'remind_at' => $dueDate->copy()->subDay()->setHour(8),
            'is_completed' => false,
            'priority' => $priority,
        ]);
    }

    /**
     * Construye la descripcion del CaseEvent.
     *
     * La API a menudo devuelve `anotacion` vacia; en ese caso usamos la
     * descripcion educativa de la regla legal (cuando aplique) para que
     * el evento no quede pelado.
     *
     * Tambien filtramos campos triviales: fechaInicial/fechaFinal solo se
     * incluyen si difieren de fechaActuacion (caso contrario son redundantes).
     *
     * @param  array<string, mixed>  $a
     */
    private function buildEventDescription(array $a, string $radicado): string
    {
        $parts = [];
        $fecha = $a['fecha'] ?? '';

        if (! empty($a['anotacion'])) {
            $parts[] = $a['anotacion'];
        } else {
            // Sin anotacion: usar descripcion educativa de la regla legal si la hay
            $rule = $this->getAlertRules(strtolower($a['tipo'] ?? ''));
            if ($rule) {
                $parts[] = $rule['descripcion'];
            }
        }

        // Solo agregar termino si las fechas representan un rango real
        $hasRangoTermino = ! empty($a['fecha_inicial'])
            && ! empty($a['fecha_final'])
            && ($a['fecha_inicial'] !== $fecha || $a['fecha_final'] !== $fecha)
            && ($a['fecha_inicial'] !== $a['fecha_final']);

        if ($hasRangoTermino) {
            $parts[] = "Termino: inicio {$a['fecha_inicial']} - fin {$a['fecha_final']}";
        }

        if (! empty($a['cod_regla'])) {
            $parts[] = "Regla CGP: {$a['cod_regla']}";
        }

        if (! empty($a['fecha_registro']) && $a['fecha_registro'] !== $fecha) {
            $parts[] = "Publicado en Rama Judicial: {$a['fecha_registro']}";
        }

        if (! empty($a['con_documentos'])) {
            $parts[] = 'La actuacion tiene documentos disponibles en Rama Judicial';
        }

        $parts[] = "Sincronizado automaticamente. Radicado: {$radicado}";

        return implode("\n", $parts);
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
