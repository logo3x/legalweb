<?php

namespace App\Services;

use App\Models\CaseEvent;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Models\TybaSyncLog;
use App\Notifications\TybaSyncNotification;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sincroniza un caso con la Rama Judicial (Tyba).
 *
 * Es la unica implementacion de la sincronizacion: la usan tanto el job
 * nocturno como el boton "Sincronizar Rama Judicial". Ademas de registrar
 * actuaciones nuevas, compara los datos del proceso contra la sincronizacion
 * anterior y guarda las diferencias en TybaSyncLog::cambios.
 */
class CaseSyncService
{
    /**
     * Campos del proceso que se comparan entre sincronizaciones.
     *
     * @var array<string, string>
     */
    public const TRACKED_FIELDS = [
        'despacho' => 'Despacho',
        'ponente' => 'Juez / ponente',
        'ubicacion' => 'Ubicacion del expediente',
        'tipo_proceso' => 'Tipo de proceso',
        'clase_proceso' => 'Clase de proceso',
        'subclase' => 'Subclase',
        'recurso' => 'Recurso',
    ];

    private const MONTHS = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'junio' => 6,
        'julio' => 7, 'agosto' => 8, 'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10,
        'noviembre' => 11, 'diciembre' => 12,
    ];

    public function __construct(public TybaService $tyba) {}

    public function sync(LegalCase $case, string $origen = 'automatico', bool $notify = true): TybaSyncLog
    {
        $radicado = (string) $case->external_case_number;
        $info = $radicado !== '' ? $this->tyba->extractProcessInfo($radicado) : null;

        if (! $info) {
            Log::warning('Tyba sync: consulta fallida', ['case' => $case->id]);
            $case->update(['last_tyba_sync' => now()]);

            return TybaSyncLog::create([
                'legal_case_id' => $case->id,
                'status' => 'error',
                'mensaje' => 'No se pudo consultar el radicado en la Rama Judicial',
                'origen' => $origen,
            ]);
        }

        $fieldChanges = $this->detectFieldChanges($case->tyba_data, $info);

        $case->update($this->buildCaseUpdates($case, $info));

        // Auto-reparacion: antes de procesar, deduplica las actuaciones que ya
        // existen para este caso (titulo + dia). Cubre duplicados creados por
        // versiones previas del sync. Conserva el id mas bajo.
        $this->dedupExistingEventsForCase($case);

        $newEvents = [];

        foreach ($info['actuaciones'] as $actuacion) {
            $date = $this->parseDate($actuacion['fecha']);

            if (! $date) {
                continue;
            }

            $title = $actuacion['tipo'].($actuacion['ciclo'] ? " ({$actuacion['ciclo']})" : '');

            // event_day (DATE con indice unico) es la unica fuente de verdad del dia.
            $eventDay = $date->toDateString();

            $existing = CaseEvent::where('legal_case_id', $case->id)
                ->where('title', $title)
                ->where('event_day', $eventDay)
                ->first();

            $newDescription = $this->buildEventDescription($actuacion);

            if ($existing) {
                $this->refreshExistingEvent($existing, $date, $newDescription);

                continue;
            }

            // Si una corrida paralela acaba de crear el mismo evento, el indice
            // unico rechaza el insert: no es error, simplemente se omite.
            try {
                CaseEvent::create([
                    'legal_case_id' => $case->id,
                    'title' => $title,
                    'event_date' => $date,
                    'event_day' => $eventDay,
                    'event_type' => 'actuacion',
                    'description' => $newDescription,
                    'user_id' => $case->user_id,
                ]);
            } catch (QueryException $e) {
                if (str_contains($e->getMessage(), 'ce_case_title_day_uniq')
                    || str_contains($e->getMessage(), 'Duplicate entry')
                    || str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                    continue;
                }

                throw $e;
            }

            $newEvents[] = [
                'fecha' => $date->format('d/m/Y'),
                'tipo' => $title,
                'anotacion' => mb_substr((string) $actuacion['anotacion'], 0, 300),
            ];

            $this->createReminderIfNeeded($case, $actuacion, $date);
        }

        $newCount = count($newEvents);
        $total = count($info['actuaciones']);
        $hasChanges = $newCount > 0 || $fieldChanges !== [];

        $mensaje = match (true) {
            $newCount > 0 => "{$newCount} nueva(s) actuacion(es) de {$total} totales",
            $fieldChanges !== [] => count($fieldChanges).' dato(s) del proceso cambiaron',
            default => "Sin novedades. {$total} actuaciones verificadas",
        };

        if ($newCount > 0 && $fieldChanges !== []) {
            $mensaje .= ' y '.count($fieldChanges).' dato(s) del proceso cambiaron';
        }

        $log = TybaSyncLog::create([
            'legal_case_id' => $case->id,
            'status' => $hasChanges ? 'ok' : 'sin_cambios',
            'nuevas_actuaciones' => $newCount,
            'mensaje' => mb_substr($mensaje, 0, 255),
            'cambios' => $hasChanges ? ['actuaciones' => $newEvents, 'campos' => $fieldChanges] : null,
            'origen' => $origen,
        ]);

        Log::info('Tyba sync completado', [
            'case' => $case->case_number,
            'nuevas' => $newCount,
            'campos' => count($fieldChanges),
        ]);

        if ($notify && $hasChanges && $case->user) {
            $case->user->notify(new TybaSyncNotification($case, $newCount, $log->cambios ?? []));
        }

        return $log;
    }

    /**
     * Compara los datos del proceso guardados contra los recien consultados.
     *
     * @param  array<string, mixed>|null  $previous
     * @param  array<string, mixed>  $current
     * @return array<int, array{campo: string, antes: string, despues: string}>
     */
    public function detectFieldChanges(?array $previous, array $current): array
    {
        if (empty($previous)) {
            return [];
        }

        $changes = [];

        foreach (self::TRACKED_FIELDS as $key => $label) {
            $before = trim((string) ($previous[$key] ?? ''));
            $after = trim((string) ($current[$key] ?? ''));

            // Un dato que antes no existia no es un cambio: solo se reporta lo que se modifico.
            if ($before === '' || $after === '' || mb_strtolower($before) === mb_strtolower($after)) {
                continue;
            }

            $changes[] = ['campo' => $label, 'antes' => $before, 'despues' => $after];
        }

        return $changes;
    }

    /**
     * Busca en la anotacion una fecha futura (audiencia, diligencia, etc.).
     *
     * Reconoce "15 de marzo de 2026", "15/03/2026" y "2026-03-15", con hora
     * opcional ("a las 9:00 a.m.", "14:30"). Devuelve la primera fecha que no
     * sea anterior a la actuacion.
     */
    public function extractScheduledDate(string $text, Carbon $actuacionDate): ?Carbon
    {
        $text = mb_strtolower($text);
        $time = '(?:\s*(?:,|a\s+la?s?|hora)?\s*(\d{1,2})[:.](\d{2})\s*(a\.?\s?m\.?|p\.?\s?m\.?)?)?';
        $monthNames = implode('|', array_keys(self::MONTHS));

        $patterns = [
            'named' => '/(\d{1,2})\s+de\s+('.$monthNames.')\s+(?:de|del)\s+(\d{4})'.$time.'/u',
            'dmy' => '/\b(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})\b'.$time.'/u',
            'ymd' => '/\b(\d{4})-(\d{2})-(\d{2})\b'.$time.'/u',
        ];

        $candidates = [];

        foreach ($patterns as $type => $pattern) {
            if (! preg_match_all($pattern, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches as $m) {
                [$day, $month, $year] = match ($type) {
                    'named' => [(int) $m[1][0], self::MONTHS[$m[2][0]], (int) $m[3][0]],
                    'dmy' => [(int) $m[1][0], (int) $m[2][0], (int) $m[3][0]],
                    'ymd' => [(int) $m[3][0], (int) $m[2][0], (int) $m[1][0]],
                };

                if (! checkdate($month, $day, $year)) {
                    continue;
                }

                $date = Carbon::create($year, $month, $day, 8, 0);

                if (! empty($m[4][0] ?? '')) {
                    $hour = (int) $m[4][0];
                    $meridiem = str_replace(['.', ' '], '', $m[6][0] ?? '');

                    if ($meridiem === 'pm' && $hour < 12) {
                        $hour += 12;
                    } elseif ($meridiem === 'am' && $hour === 12) {
                        $hour = 0;
                    }

                    if ($hour <= 23) {
                        $date->setTime($hour, (int) $m[5][0]);
                    }
                }

                if ($date->copy()->startOfDay()->lt($actuacionDate->copy()->startOfDay())
                    || $date->gt($actuacionDate->copy()->addYears(3))) {
                    continue;
                }

                $candidates[$m[0][1]] = $date;
            }
        }

        if ($candidates === []) {
            return null;
        }

        ksort($candidates);

        return reset($candidates);
    }

    /**
     * @param  array<string, mixed>  $info
     * @return array<string, mixed>
     */
    private function buildCaseUpdates(LegalCase $case, array $info): array
    {
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

        if (empty($case->opposing_party) && ! empty($info['sujetos'])) {
            $demandados = collect($info['sujetos'])
                ->filter(fn (array $s): bool => str_contains(mb_strtolower($s['rol']), 'demandado'))
                ->pluck('nombre')
                ->unique()
                ->join(', ');

            if ($demandados) {
                $updates['opposing_party'] = $demandados;
            }
        }

        // Solo se regenera la descripcion importada; nunca la que escribio el abogado.
        if (blank($case->description) || str_starts_with((string) $case->description, 'Importado desde Rama Judicial')) {
            $updates['description'] = $this->buildCaseDescription($info);
        }

        return $updates;
    }

    /**
     * @param  array<string, mixed>  $info
     */
    private function buildCaseDescription(array $info): string
    {
        $description = "Importado desde Rama Judicial\n";
        $description .= "Tipo: {$info['tipo_proceso']}\nClase: {$info['clase_proceso']}\n";
        $description .= "Departamento: {$info['departamento']}\n";
        $description .= "Despacho: {$info['despacho']}\n";

        if (! empty($info['ponente'])) {
            $description .= "Ponente: {$info['ponente']}\n";
        }

        if (! empty($info['sujetos'])) {
            $description .= "\nSujetos procesales:\n";
            foreach ($info['sujetos'] as $s) {
                $description .= "- {$s['rol']}: {$s['nombre']}\n";
            }
        }

        return $description;
    }

    /**
     * Normaliza la hora y reemplaza descripciones genericas de versiones anteriores.
     */
    private function refreshExistingEvent(CaseEvent $existing, Carbon $date, string $newDescription): void
    {
        $updates = [];

        if ($existing->event_date && $existing->event_date->format('H:i:s') !== '00:00:00') {
            $updates['event_date'] = $date;
        }

        $oldDescGeneric = $existing->description === null
            || str_contains($existing->description, 'Sincronizado desde Rama Judicial. Radicado:')
            || str_contains($existing->description, 'Sincronizado automaticamente. Radicado:');

        if ($oldDescGeneric && $newDescription !== '') {
            $updates['description'] = $newDescription;
        }

        if ($updates) {
            $existing->update($updates);
        }
    }

    /**
     * Crea recordatorios para actuaciones con plazo o fecha asociada.
     *
     * Fuentes de fecha (en orden de preferencia):
     *  1. fechaFinal devuelta por la API.
     *  2. Fecha programada escrita en la anotacion (audiencias, diligencias).
     *  3. Plazo legal en dias habiles segun el tipo de actuacion.
     *
     * @param  array<string, mixed>  $actuacion
     */
    private function createReminderIfNeeded(LegalCase $case, array $actuacion, Carbon $date): void
    {
        $alertRules = $this->getAlertRules(strtolower($actuacion['tipo']));

        $dueDate = null;
        $fuenteFecha = '';
        $isScheduledEvent = false;

        if (! empty($actuacion['fecha_final'])) {
            $dueDate = $this->parseDate($actuacion['fecha_final']);
            $fuenteFecha = 'Termino tomado de la Rama Judicial';
        }

        if (! $dueDate && ! empty($actuacion['anotacion'])) {
            $wantsSchedule = ($alertRules['tipo_recordatorio'] ?? null) === 'audiencia'
                || preg_match('/audiencia|diligencia|fija\s+fecha|se\s+fija|se\s+señala|programa/u', mb_strtolower($actuacion['anotacion']));

            if ($wantsSchedule) {
                $dueDate = $this->extractScheduledDate($actuacion['anotacion'], $date);

                if ($dueDate) {
                    $fuenteFecha = 'Fecha detectada en la anotacion de la actuacion';
                    $isScheduledEvent = true;
                    $alertRules ??= [
                        'dias' => 0, 'tipo_recordatorio' => 'audiencia', 'prioridad_minima' => 'alta',
                        'descripcion' => 'Se programo una diligencia. Verificar fecha y preparar.',
                    ];
                }
            }
        }

        if (! $dueDate && $alertRules && $alertRules['dias'] > 0) {
            $deadline = app(JudicialCalendarService::class)
                ->calculateDeadline($date, $alertRules['dias'], 'business');
            $dueDate = $deadline['deadline'];
            $fuenteFecha = "Plazo de {$alertRules['dias']} dias habiles desde la actuacion";
        }

        if (! $dueDate || $dueDate->lt(now()->subDays(3))) {
            return;
        }

        $title = $actuacion['tipo'].' - '.$case->case_number;

        // Match exacto en (caso, titulo, vencimiento): evita duplicados sin depender de timezone.
        if (Reminder::where('legal_case_id', $case->id)
            ->where('title', $title)
            ->where('due_date', $dueDate)
            ->exists()) {
            return;
        }

        $diasRestantes = (int) now()->diffInDays($dueDate, false);
        $priority = match (true) {
            $diasRestantes <= 1 => 'urgente',
            $diasRestantes <= 3 => 'alta',
            $diasRestantes <= 7 => 'media',
            default => 'baja',
        };

        if ($alertRules) {
            if ($alertRules['prioridad_minima'] === 'alta' && $priority === 'baja') {
                $priority = 'media';
            }
            if ($alertRules['prioridad_minima'] === 'urgente') {
                $priority = 'urgente';
            }
        }

        $descripcionBase = $alertRules['descripcion'] ?? 'Termino judicial detectado en sincronizacion automatica.';
        $venceLabel = $isScheduledEvent ? 'Programada para' : 'Vence';
        $dueFormat = $isScheduledEvent ? 'd/m/Y H:i' : 'd/m/Y';

        Reminder::create([
            'firm_id' => $case->firm_id,
            'user_id' => $case->user_id,
            'legal_case_id' => $case->id,
            'title' => $title,
            'description' => "{$descripcionBase}\n"
                ."{$fuenteFecha}.\n"
                ."Actuacion: {$date->format('d/m/Y')} | {$venceLabel}: {$dueDate->format($dueFormat)}\n"
                ."Radicado: {$case->external_case_number}",
            'type' => $alertRules['tipo_recordatorio'] ?? 'vencimiento',
            'due_date' => $dueDate,
            'remind_at' => $dueDate->copy()->subDay()->setTime(8, 0),
            'is_completed' => false,
            'priority' => $priority,
        ]);
    }

    /**
     * Auto-repara duplicados en CaseEvent para el caso: agrupa por (titulo,
     * event_day), conserva el id mas bajo y elimina el resto.
     */
    private function dedupExistingEventsForCase(LegalCase $case): void
    {
        DB::table('case_events')
            ->where('legal_case_id', $case->id)
            ->whereNull('event_day')
            ->update(['event_day' => DB::raw('DATE(event_date)')]);

        $groups = DB::table('case_events')
            ->selectRaw('title, event_day, COUNT(*) as cnt, MIN(id) as keep_id')
            ->where('legal_case_id', $case->id)
            ->whereNotNull('event_day')
            ->groupBy('title', 'event_day')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $g) {
            CaseEvent::where('legal_case_id', $case->id)
                ->where('title', $g->title)
                ->where('event_day', $g->event_day)
                ->where('id', '!=', $g->keep_id)
                ->delete();
        }
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
    private function buildEventDescription(array $a): string
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
            $parts[] = 'Tiene documentos asociados en la Rama Judicial.';
        }

        // Si despues de todo no logramos extraer nada significativo, dejamos
        // descripcion vacia. La linea generica "Sincronizado..." solo agrega
        // ruido y compite con la informacion real en la UI.
        return implode("\n\n", array_filter($parts));
    }

    /**
     * Reglas de alerta segun tipo de actuacion.
     * Dias = plazo legal en dias habiles desde la actuacion.
     *
     * Cuando una actuacion no tiene `anotacion` en la API, la descripcion
     * de la regla se usa como texto del evento. Por eso conviene tener
     * cobertura amplia: si el tipo no cae en una regla especifica, los
     * fallbacks genericos al final ('agregar memorial', 'auto', ...)
     * garantizan que el evento traiga al menos contexto educativo.
     *
     * @return array{dias: int, tipo_recordatorio: string, prioridad_minima: string, descripcion: string}|null
     */
    private function getAlertRules(string $tipo): ?array
    {
        return match (true) {
            // Autos especificos
            str_contains($tipo, 'auto admite') => [
                'dias' => 20, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'alta',
                'descripcion' => 'Auto admisorio de demanda. Plazo para notificar y/o contestar.',
            ],
            str_contains($tipo, 'auto inadmite') => [
                'dias' => 5, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'urgente',
                'descripcion' => 'Auto inadmisorio. Plazo de 5 dias para subsanar antes de rechazo.',
            ],
            str_contains($tipo, 'auto rechaza') => [
                'dias' => 5, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'alta',
                'descripcion' => 'Auto que rechaza la demanda. Revisar si procede recurso (reposicion o apelacion).',
            ],
            str_contains($tipo, 'auto niega') => [
                'dias' => 3, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'alta',
                'descripcion' => 'Auto que niega pretension o solicitud. Plazo de 3 dias para reposicion.',
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
            str_contains($tipo, 'auto decreta') => [
                'dias' => 3, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'media',
                'descripcion' => 'Auto que decreta prueba o medida. Revisar contenido y plazos.',
            ],
            str_contains($tipo, 'auto reconoce') => [
                'dias' => 0, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'media',
                'descripcion' => 'Auto que reconoce personeria o apoderado. Verificar contenido.',
            ],
            str_contains($tipo, 'auto aprueba') => [
                'dias' => 0, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'media',
                'descripcion' => 'Auto aprobatorio. Revisar contenido y eventuales obligaciones.',
            ],
            str_contains($tipo, 'auto concede') => [
                'dias' => 0, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'media',
                'descripcion' => 'Auto que concede recurso o solicitud. Verificar tramite siguiente.',
            ],
            str_contains($tipo, 'auto resuelve') => [
                'dias' => 3, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'media',
                'descripcion' => 'Auto que resuelve solicitud o incidente. Revisar si procede recurso.',
            ],
            str_contains($tipo, 'auto continua') => [
                'dias' => 0, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'baja',
                'descripcion' => 'Auto que continua actuacion. Sin plazo automatico, verificar siguiente paso.',
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

            // Memoriales y radicaciones
            str_contains($tipo, 'agregar memorial')
                || str_contains($tipo, 'memorial allegado')
                || str_contains($tipo, 'recibe memorial') => [
                    'dias' => 0, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'baja',
                    'descripcion' => 'Memorial radicado en el expediente. Revisar contenido y verificar si requiere respuesta.',
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
            str_contains($tipo, 'edicto') || str_contains($tipo, 'aviso') => [
                'dias' => 5, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'media',
                'descripcion' => 'Notificacion por edicto/aviso. Verificar plazos de respuesta.',
            ],

            // Tramite del expediente
            str_contains($tipo, 'pasa al despacho')
                || str_contains($tipo, 'pasa a despacho')
                || str_contains($tipo, 'sustanciacion')
                || str_contains($tipo, 'pasa a fallo') => [
                    'dias' => 0, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'baja',
                    'descripcion' => 'Expediente en tramite interno del despacho. No requiere accion inmediata; pendiente de providencia.',
                ],
            str_contains($tipo, 'constancia') => [
                'dias' => 0, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'baja',
                'descripcion' => 'Constancia secretarial. Anotacion administrativa del despacho.',
            ],
            str_contains($tipo, 'embargo') => [
                'dias' => 0, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'alta',
                'descripcion' => 'Actuacion relacionada con embargo. Revisar bienes y oficios.',
            ],

            // Catch-all para autos sin coincidencia especifica
            str_contains($tipo, 'auto') => [
                'dias' => 0, 'tipo_recordatorio' => 'vencimiento', 'prioridad_minima' => 'media',
                'descripcion' => 'Providencia del despacho. Revisar contenido y eventuales recursos o plazos.',
            ],

            default => null,
        };
    }

    private function parseDate(string $date): ?Carbon
    {
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y'];

        foreach ($formats as $format) {
            try {
                // startOfDay() normaliza la hora; createFromFormat sin hora
                // deja la hora actual y rompe los chequeos de duplicado.
                return Carbon::createFromFormat($format, trim($date))->startOfDay();
            } catch (\Exception) {
                continue;
            }
        }

        return null;
    }
}
