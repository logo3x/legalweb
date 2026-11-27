<?php

namespace App\Services;

use App\Models\CaseEvent;
use App\Models\CaseType;
use App\Models\LegalCase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;

/**
 * Crea un caso a partir de un radicado: primero en la Rama Judicial (con sujetos y actuaciones)
 * y, si no esta, en SAMAI. Lo usan Casos (Importar proceso, importacion masiva) y Buscar procesos.
 */
class CaseImporter
{
    public function __construct(private User $user) {}

    /**
     * Mensaje para el abogado si el plan ya no permite mas casos activos; null si puede importar.
     */
    public function caseLimitMessage(): ?string
    {
        $firm = $this->user->firm;

        if (! $firm || $firm->planLimits()->canAddActiveCase()) {
            return null;
        }

        return FirmPlan::upgradeMessage('El plan '.$firm->planLimits()->label().' permite '.$firm->planLimits()->maxActiveCases().' casos activos.');
    }

    /**
     * @return array{error: ?string, detail: ?string, imported: bool, message: ?string, case: ?LegalCase}
     */
    public function import(string $radicado, int $clientId, int $userId): array
    {
        $firmId = $this->user->firm_id;

        if (LegalCase::withoutGlobalScopes()->where('firm_id', $firmId)->where('external_case_number', $radicado)->exists()) {
            return ['error' => 'Radicado ya existe', 'detail' => "Ya tiene un caso con el radicado {$radicado}.", 'imported' => false, 'message' => null, 'case' => null];
        }

        $info = null;

        try {
            $info = app(TybaService::class)->extractProcessInfo($radicado);
        } catch (\Exception) {
            // Timeout u otro error de conexion: se intenta en SAMAI.
        }

        // Si no esta en la Rama Judicial, se intenta en SAMAI (jurisdiccion contencioso-administrativa)
        if (! $info || empty($info['despacho'])) {
            return $this->importFromSamai($radicado, $clientId, $userId);
        }

        $title = ($info['clase_proceso'] ?: 'Proceso Judicial').' - '.$radicado;
        $court = $info['despacho'];
        $judge = ! empty($info['ponente']) ? mb_convert_case(mb_strtolower($info['ponente']), MB_CASE_TITLE, 'UTF-8') : null;
        $caseTypeId = CaseType::firstOrCreate(['name' => $info['especialidad'] ?: 'General'])->id;
        $startedAt = null;

        $demandados = collect($info['sujetos'])->filter(fn ($s) => str_contains(strtolower($s['rol']), 'demandado'))->pluck('nombre')->unique()->join(', ');
        $demandantes = collect($info['sujetos'])->filter(fn ($s) => str_contains(strtolower($s['rol']), 'demandante'))->pluck('nombre')->unique()->join(', ');
        $opposingParty = $demandados ?: $demandantes;

        if ($demandantes && $demandados) {
            $title = ($info['clase_proceso'] ?: 'Proceso').' - '.mb_substr($demandantes, 0, 40).' vs '.mb_substr($demandados, 0, 40);
        }

        $description = "Importado desde Rama Judicial\nTipo: {$info['tipo_proceso']}\nClase: {$info['clase_proceso']}\nDepartamento: {$info['departamento']}\nDespacho: {$info['despacho']}\n";
        if (! empty($info['ponente'])) {
            $description .= "Ponente: {$info['ponente']}\n";
        }
        if (! empty($info['sujetos'])) {
            $description .= "\nSujetos procesales:\n";
            foreach ($info['sujetos'] as $s) {
                $description .= "- {$s['rol']}: {$s['nombre']}\n";
            }
        }

        if ($info['fecha_publicacion']) {
            foreach (['d/m/Y', 'j/m/Y', 'Y-m-d'] as $fmt) {
                try {
                    $startedAt = Carbon::createFromFormat($fmt, trim($info['fecha_publicacion']));
                    break;
                } catch (\Exception) {
                }
            }
        }

        $caseNumber = LegalCase::nextCaseNumber();

        try {
            $case = LegalCase::create([
                'firm_id' => $firmId,
                'case_number' => $caseNumber,
                'external_case_number' => $radicado,
                'title' => $title,
                'description' => $description,
                'case_type_id' => $caseTypeId,
                'client_id' => $clientId,
                'user_id' => $userId,
                'status' => 'abierto',
                'priority' => 'media',
                'court' => $court,
                'judge' => $judge,
                'opposing_party' => $opposingParty,
                'started_at' => $startedAt,
                'last_tyba_sync' => now(),
                'tyba_data' => collect($info)->except(['sujetos', 'actuaciones'])->toArray(),
            ]);
        } catch (QueryException) {
            return ['error' => 'Error al crear caso', 'detail' => 'No se pudo crear el caso.', 'imported' => false, 'message' => null, 'case' => null];
        }

        // Misma descripcion que la vigilancia nocturna (anotacion, termino, publicacion, documentos)
        // y el id de la actuacion en la Rama, para que la consulta siguiente no la duplique.
        $sync = app(CaseSyncService::class);
        $actuacionesCount = 0;

        foreach ($info['actuaciones'] ?? [] as $a) {
            $date = $sync->parseDate((string) $a['fecha']);

            if (! $date) {
                continue;
            }

            try {
                CaseEvent::create([
                    'legal_case_id' => $case->id,
                    'title' => $a['tipo'].(! empty($a['ciclo']) ? " ({$a['ciclo']})" : ''),
                    'event_date' => $date,
                    'event_day' => $date->toDateString(),
                    'rama_ref' => $a['ref'] ?? null,
                    'rama_documents' => ! empty($a['con_documentos']),
                    'event_type' => 'actuacion',
                    'description' => $sync->buildEventDescription($a) ?: null,
                    'user_id' => $userId,
                ]);
            } catch (QueryException) {
                // La Rama repitio la misma actuacion: se omite.
                continue;
            }

            $actuacionesCount++;
        }

        $msg = "Caso {$caseNumber} importado con ".count($info['sujetos'])." sujetos y {$actuacionesCount} actuaciones.";

        return ['error' => null, 'detail' => null, 'imported' => true, 'message' => $msg, 'case' => $case];
    }

    /**
     * Importa un proceso de SAMAI. SAMAI solo expone los datos generales del
     * proceso: las actuaciones se consultan en el enlace de SAMAI.
     *
     * @return array{error: ?string, detail: ?string, imported: bool, message: ?string, case: ?LegalCase}
     */
    private function importFromSamai(string $radicado, int $clientId, int $userId): array
    {
        $notFound = ['error' => null, 'detail' => null, 'imported' => false, 'message' => null, 'case' => null];

        try {
            $item = app(SamaiService::class)->findByRadicado($radicado);
        } catch (\Throwable) {
            return $notFound;
        }

        if (! $item) {
            return $notFound;
        }

        $parties = trim(mb_substr($item['demandante'], 0, 40).' vs '.mb_substr($item['demandado'], 0, 40), ' vs');
        $title = ($item['clase'] !== '' ? mb_convert_case(mb_strtolower($item['clase']), MB_CASE_TITLE, 'UTF-8') : 'Proceso Administrativo')
            .($parties !== '' ? ' - '.$parties : ' - '.$radicado);

        $description = "Importado desde SAMAI (Consejo de Estado)\n"
            ."Clase: {$item['clase']}\n"
            ."Corporacion: {$item['corporacion_nombre']}\n"
            ."Despacho: {$item['despacho']}\n"
            ."Ponente: {$item['ponente']}\n"
            ."Asunto: {$item['asunto']}\n"
            ."Demandante: {$item['demandante']}\n"
            ."Demandado: {$item['demandado']}\n"
            ."Estado: {$item['vigente']}\n\n"
            ."Actuaciones: {$item['url']}";

        try {
            $case = LegalCase::create([
                'firm_id' => $this->user->firm_id,
                'case_number' => LegalCase::nextCaseNumber(),
                'external_case_number' => $radicado,
                'judicial_source' => LegalCase::SOURCE_SAMAI,
                'title' => mb_substr($title, 0, 250),
                'description' => $description,
                'case_type_id' => CaseType::firstOrCreate(['name' => 'Administrativo'])->id,
                'client_id' => $clientId,
                'user_id' => $userId,
                'status' => $item['vigente'] === 'Archivado' ? 'archivado' : 'abierto',
                'priority' => 'media',
                'court' => trim($item['despacho'].($item['corporacion_nombre'] ? ' - '.$item['corporacion_nombre'] : ''), ' -'),
                'judge' => $item['ponente'] !== '' ? mb_convert_case(mb_strtolower($item['ponente']), MB_CASE_TITLE, 'UTF-8') : null,
                'opposing_party' => $item['demandado'] ?: $item['demandante'],
                'started_at' => $item['fecha_ingreso'] ?: null,
                'last_tyba_sync' => now(),
                'tyba_data' => $item,
            ]);
        } catch (QueryException) {
            return ['error' => 'Error al crear caso', 'detail' => 'No se pudo crear el caso.', 'imported' => false, 'message' => null, 'case' => null];
        }

        return [
            'error' => null,
            'detail' => null,
            'imported' => true,
            'message' => "Caso {$case->case_number} importado desde SAMAI ({$item['corporacion_nombre']}). Las actuaciones se consultan con el boton \"Ver en SAMAI\" del caso.",
            'case' => $case,
        ];
    }
}
