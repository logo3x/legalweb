<?php

namespace App\Filament\Resources\LegalCases\Pages;

use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\CaseEvent;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\TybaService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\QueryException;

class ListLegalCases extends ListRecords
{
    protected static string $resource = LegalCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import_tyba')
                ->label('Importar desde Tyba')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->modalWidth('xl')
                ->modalHeading('Importar Proceso desde Rama Judicial')
                ->modalDescription('Ingrese el radicado judicial. El sistema importara automaticamente toda la informacion del proceso, sujetos y actuaciones.')
                ->modalSubmitActionLabel('Importar Proceso')
                ->form([
                    TextInput::make('radicado')
                        ->label('Codigo de Proceso (Radicado)')
                        ->required()
                        ->minLength(20)
                        ->maxLength(50)
                        ->placeholder('Ej: 68081310300120240001800')
                        ->helperText('23 digitos del radicado asignado por la Rama Judicial.'),
                    Select::make('client_id')
                        ->label('Cliente')
                        ->options(fn () => Client::where('firm_id', auth()->user()->firm_id)
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->id => "{$c->first_name} {$c->last_name}"]))
                        ->required()
                        ->searchable(),
                    Select::make('user_id')
                        ->label('Abogado Responsable')
                        ->options(fn () => User::where('firm_id', auth()->user()->firm_id)->pluck('name', 'id'))
                        ->default(fn () => auth()->id())
                        ->required(),
                ])
                ->action(function (array $data) {
                    $radicado = preg_replace('/[^0-9]/', '', $data['radicado']);
                    $result = $this->importSingleRadicado($radicado, $data['client_id'], $data['user_id']);

                    if ($result['error']) {
                        Notification::make()->title($result['error'])->body($result['detail'] ?? '')->warning()->send();

                        return;
                    }

                    Notification::make()
                        ->title($result['imported'] ? 'Importacion exitosa' : 'Caso creado')
                        ->body($result['message'])
                        ->color($result['imported'] ? 'success' : 'warning')
                        ->persistent()
                        ->send();

                    $this->redirect(LegalCaseResource::getUrl('view', ['record' => $result['case']]));
                }),

            Action::make('import_masivo')
                ->label('Importacion Masiva')
                ->icon('heroicon-o-arrow-down-on-square-stack')
                ->color('gray')
                ->modalWidth('xl')
                ->modalHeading('Importacion Masiva de Procesos')
                ->modalDescription('Pegue una lista de radicados (uno por linea). Se importaran todos los procesos encontrados en la Rama Judicial.')
                ->modalSubmitActionLabel('Importar Todos')
                ->form([
                    Textarea::make('radicados')
                        ->label('Radicados (uno por linea)')
                        ->required()
                        ->rows(8)
                        ->placeholder("68081310300120240001800\n05001310500120230012300\n11001310304120220045600")
                        ->helperText('Puede pegar hasta 20 radicados a la vez. Cada uno en una linea separada.'),
                    Select::make('client_id')
                        ->label('Cliente (para todos los casos)')
                        ->options(fn () => Client::where('firm_id', auth()->user()->firm_id)
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->id => "{$c->first_name} {$c->last_name}"]))
                        ->required()
                        ->searchable(),
                    Select::make('user_id')
                        ->label('Abogado Responsable')
                        ->options(fn () => User::where('firm_id', auth()->user()->firm_id)->pluck('name', 'id'))
                        ->default(fn () => auth()->id())
                        ->required(),
                ])
                ->action(function (array $data) {
                    $lines = preg_split('/[\r\n]+/', trim($data['radicados']));
                    $radicados = collect($lines)
                        ->map(fn ($l) => preg_replace('/[^0-9]/', '', trim($l)))
                        ->filter(fn ($r) => strlen($r) >= 20)
                        ->unique()
                        ->take(20)
                        ->values();

                    if ($radicados->isEmpty()) {
                        Notification::make()->title('Sin radicados validos')->body('No se encontraron radicados validos (minimo 20 digitos).')->warning()->send();

                        return;
                    }

                    $results = [];
                    $imported = 0;
                    $skipped = 0;
                    $notFound = 0;
                    $errors = 0;

                    foreach ($radicados as $radicado) {
                        $result = $this->importSingleRadicado($radicado, $data['client_id'], $data['user_id']);

                        if ($result['error']) {
                            if (str_contains($result['error'], 'ya existe')) {
                                $results[] = ['radicado' => $radicado, 'status' => 'duplicado', 'msg' => 'Ya existe en su firma'];
                                $skipped++;
                            } else {
                                $results[] = ['radicado' => $radicado, 'status' => 'error', 'msg' => $result['error']];
                                $errors++;
                            }
                        } elseif (! $result['imported']) {
                            $results[] = ['radicado' => $radicado, 'status' => 'no_encontrado', 'msg' => 'Caso creado pero no se encontro en Rama Judicial'];
                            $notFound++;
                        } else {
                            $results[] = ['radicado' => $radicado, 'status' => 'ok', 'msg' => $result['message']];
                            $imported++;
                        }
                    }

                    // Construir reporte detallado
                    $detail = '';
                    foreach ($results as $r) {
                        $icon = match ($r['status']) {
                            'ok' => '✓',
                            'duplicado' => '⊘',
                            'no_encontrado' => '?',
                            default => '✗',
                        };
                        $detail .= "{$icon} {$r['radicado']} - {$r['msg']}\n";
                    }

                    $summary = collect([
                        $imported > 0 ? "{$imported} importado(s)" : null,
                        $skipped > 0 ? "{$skipped} duplicado(s)" : null,
                        $notFound > 0 ? "{$notFound} no encontrado(s)" : null,
                        $errors > 0 ? "{$errors} con error" : null,
                    ])->filter()->join(', ');

                    Notification::make()
                        ->title("Importacion masiva: {$radicados->count()} radicados procesados")
                        ->body("{$summary}\n\n{$detail}")
                        ->color($imported > 0 ? 'success' : 'warning')
                        ->persistent()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }

    /**
     * Importar un radicado individual. Retorna resultado estandarizado.
     *
     * @return array{error: ?string, detail: ?string, imported: bool, message: ?string, case: ?LegalCase}
     */
    private function importSingleRadicado(string $radicado, int $clientId, int $userId): array
    {
        $firmId = auth()->user()->firm_id;

        // Verificar duplicado
        if (LegalCase::withoutGlobalScopes()->where('firm_id', $firmId)->where('external_case_number', $radicado)->exists()) {
            return ['error' => 'Radicado ya existe', 'detail' => "Ya tiene un caso con el radicado {$radicado}.", 'imported' => false, 'message' => null, 'case' => null];
        }

        // Consultar API
        $tyba = app(TybaService::class);
        $info = $tyba->extractProcessInfo($radicado);

        // Construir datos
        $title = 'Proceso Judicial - '.$radicado;
        $court = null;
        $judge = null;
        $opposingParty = null;
        $description = null;
        $startedAt = null;
        $caseTypeId = CaseType::first()?->id;

        if ($info && ! empty($info['despacho'])) {
            $title = ($info['clase_proceso'] ?: 'Proceso Judicial').' - '.$radicado;
            $court = $info['despacho'];
            $judge = ! empty($info['ponente']) ? mb_convert_case(mb_strtolower($info['ponente']), MB_CASE_TITLE, 'UTF-8') : null;
            $caseTypeId = CaseType::firstOrCreate(['name' => $info['especialidad'] ?: 'General'])->id;

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
        }

        // Generar case_number unico
        $year = now()->format('Y');
        $lastCase = LegalCase::withoutGlobalScopes()
            ->where('case_number', 'like', "LW-%-{$year}")
            ->orderByRaw('CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(case_number, "-", 2), "-", -1) AS UNSIGNED) DESC')
            ->first();
        $nextNum = 1;
        if ($lastCase && preg_match('/LW-(\d+)-/', $lastCase->case_number, $m)) {
            $nextNum = ((int) $m[1]) + 1;
        }
        $caseNumber = sprintf('LW-%04d-%s', $nextNum, $year);

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
                'last_tyba_sync' => $info ? now() : null,
                'tyba_data' => $info ? collect($info)->except(['sujetos', 'actuaciones'])->toArray() : null,
            ]);
        } catch (QueryException) {
            return ['error' => 'Error al crear caso', 'detail' => 'No se pudo crear el caso.', 'imported' => false, 'message' => null, 'case' => null];
        }

        // Importar actuaciones
        $actuacionesCount = 0;
        if ($info && ! empty($info['actuaciones'])) {
            foreach ($info['actuaciones'] as $a) {
                $date = null;
                foreach (['d/m/Y', 'j/m/Y', 'Y-m-d'] as $fmt) {
                    try {
                        $date = Carbon::createFromFormat($fmt, trim($a['fecha']));
                        break;
                    } catch (\Exception) {
                    }
                }
                if (! $date) {
                    continue;
                }

                CaseEvent::create([
                    'legal_case_id' => $case->id,
                    'title' => $a['tipo'].($a['ciclo'] ? " ({$a['ciclo']})" : ''),
                    'event_date' => $date,
                    'event_type' => 'actuacion',
                    'description' => 'Importado desde Rama Judicial. Radicado: '.$radicado,
                    'user_id' => $userId,
                ]);
                $actuacionesCount++;
            }
        }

        $imported = $info && ! empty($info['despacho']);
        $msg = $imported
            ? "Caso {$caseNumber} importado con ".count($info['sujetos'])." sujetos y {$actuacionesCount} actuaciones."
            : "Caso {$caseNumber} creado. No se encontraron datos en Rama Judicial.";

        return ['error' => null, 'detail' => null, 'imported' => $imported, 'message' => $msg, 'case' => $case];
    }
}
