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
                ->modalDescription('Ingrese el radicado judicial. El sistema importara automaticamente toda la informacion del proceso, sujetos y actuaciones desde Tyba.')
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

                    // Verificar duplicado
                    $exists = LegalCase::withoutGlobalScopes()
                        ->where('firm_id', auth()->user()->firm_id)
                        ->where('external_case_number', $radicado)
                        ->exists();

                    if ($exists) {
                        Notification::make()
                            ->title('Radicado ya existe')
                            ->body("Ya tiene un caso con el radicado {$radicado}.")
                            ->warning()
                            ->send();

                        return;
                    }

                    // Importar datos via Browserless
                    $tyba = app(TybaService::class);
                    $info = $tyba->extractProcessInfo($radicado);

                    // Construir datos del caso
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
                        $judge = ! empty($info['ponente'])
                            ? mb_convert_case(mb_strtolower($info['ponente']), MB_CASE_TITLE, 'UTF-8')
                            : null;
                        $caseTypeId = CaseType::firstOrCreate(['name' => $info['especialidad'] ?: 'General'])->id;

                        // Sujetos
                        $demandados = collect($info['sujetos'])->filter(fn ($s) => str_contains(strtolower($s['rol']), 'demandado'))->pluck('nombre')->unique()->join(', ');
                        $demandantes = collect($info['sujetos'])->filter(fn ($s) => str_contains(strtolower($s['rol']), 'demandante'))->pluck('nombre')->unique()->join(', ');
                        $opposingParty = $demandados ?: $demandantes;

                        if ($demandantes && $demandados) {
                            $title = ($info['clase_proceso'] ?: 'Proceso').' - '.mb_substr($demandantes, 0, 40).' vs '.mb_substr($demandados, 0, 40);
                        }

                        // Descripcion
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

                        // Fecha
                        if ($info['fecha_publicacion']) {
                            foreach (['d/m/Y', 'j/m/Y', 'j/m/y', 'Y-m-d'] as $fmt) {
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
                            'firm_id' => auth()->user()->firm_id,
                            'case_number' => $caseNumber,
                            'external_case_number' => $radicado,
                            'title' => $title,
                            'description' => $description,
                            'case_type_id' => $caseTypeId,
                            'client_id' => $data['client_id'],
                            'user_id' => $data['user_id'],
                            'status' => 'abierto',
                            'priority' => 'media',
                            'court' => $court,
                            'judge' => $judge,
                            'opposing_party' => $opposingParty,
                            'started_at' => $startedAt,
                        ]);
                    } catch (QueryException) {
                        Notification::make()
                            ->title('Error al crear caso')
                            ->body('No se pudo crear el caso. Intente nuevamente.')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Importar actuaciones como CaseEvents
                    $actuacionesCount = 0;
                    if ($info && ! empty($info['actuaciones'])) {
                        foreach ($info['actuaciones'] as $a) {
                            $date = null;
                            foreach (['d/m/Y', 'j/m/Y', 'j/m/y', 'Y-m-d'] as $fmt) {
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
                                'description' => 'Importado desde Tyba. Radicado: '.$radicado,
                                'user_id' => $data['user_id'],
                            ]);
                            $actuacionesCount++;
                        }
                    }

                    $imported = $info && ! empty($info['despacho']);
                    $msg = $imported
                        ? "Caso {$caseNumber} importado de Tyba con ".count($info['sujetos'])." sujetos y {$actuacionesCount} actuaciones."
                        : "Caso {$caseNumber} creado con radicado {$radicado}. No se pudieron importar datos de Tyba, complete manualmente.";

                    Notification::make()
                        ->title($imported ? 'Importacion exitosa' : 'Caso creado')
                        ->body($msg)
                        ->color($imported ? 'success' : 'warning')
                        ->persistent()
                        ->send();

                    $this->redirect(LegalCaseResource::getUrl('view', ['record' => $case]));
                }),
            CreateAction::make(),
        ];
    }
}
