<?php

namespace App\Filament\Resources\LegalCases\Pages;

use App\Filament\Resources\LegalCases\LegalCaseResource;
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
                ->modalDescription('Ingrese el codigo de proceso (radicado) de 23 digitos para importar automaticamente la informacion del juzgado.')
                ->modalSubmitActionLabel('Importar Proceso')
                ->form([
                    TextInput::make('radicado')
                        ->label('Codigo de Proceso (Radicado)')
                        ->required()
                        ->minLength(20)
                        ->maxLength(50)
                        ->placeholder('Ej: 68081310300120240001800')
                        ->helperText('23 digitos del radicado judicial asignado por la Rama Judicial.'),
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
                    $tyba = app(TybaService::class);
                    $info = $tyba->extractProcessInfo($data['radicado']);

                    if (! $info) {
                        Notification::make()
                            ->title('Proceso no encontrado')
                            ->body('No se encontro informacion para el radicado ingresado. Verifique el numero e intente nuevamente.')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Usar radicado del input si no se extrajo del HTML
                    $radicado = $info['codigo_proceso'] ?: preg_replace('/[^0-9]/', '', $data['radicado']);
                    $info['codigo_proceso'] = $radicado;

                    // Verificar que no exista ya
                    $exists = LegalCase::withoutGlobalScopes()
                        ->where('firm_id', auth()->user()->firm_id)
                        ->where('external_case_number', $radicado)
                        ->exists();

                    if ($exists) {
                        Notification::make()
                            ->title('Proceso ya existe')
                            ->body("Ya existe un caso con el radicado {$radicado} en su firma.")
                            ->warning()
                            ->send();

                        return;
                    }

                    // Buscar o crear CaseType basado en especialidad
                    $caseType = CaseType::firstOrCreate(
                        ['name' => $info['especialidad'] ?: 'General'],
                    );

                    // Generar case_number
                    $year = now()->format('Y');
                    $lastCase = LegalCase::withoutGlobalScopes()
                        ->where('firm_id', auth()->user()->firm_id)
                        ->where('case_number', 'like', "LW-%-{$year}")
                        ->orderByDesc('case_number')
                        ->first();
                    $nextNum = 1;
                    if ($lastCase && preg_match('/LW-(\d+)-/', $lastCase->case_number, $m)) {
                        $nextNum = ((int) $m[1]) + 1;
                    }
                    $caseNumber = sprintf('LW-%04d-%s', $nextNum, $year);

                    // Determinar contraparte
                    $demandados = collect($info['sujetos'])
                        ->filter(fn ($s) => str_contains(strtolower($s['rol']), 'demandado'))
                        ->pluck('nombre')
                        ->join(', ');
                    $demandantes = collect($info['sujetos'])
                        ->filter(fn ($s) => str_contains(strtolower($s['rol']), 'demandante'))
                        ->pluck('nombre')
                        ->join(', ');

                    // Titulo: "Clase Proceso - Demandante vs Demandado"
                    $title = $info['clase_proceso'] ?: 'Proceso Judicial';
                    if ($demandantes && $demandados) {
                        $title .= ' - '.mb_substr($demandantes, 0, 50).' vs '.mb_substr($demandados, 0, 50);
                    }

                    // Descripcion con toda la info de Tyba
                    $description = "Importado desde Rama Judicial (Tyba)\n\n";
                    $description .= "Tipo: {$info['tipo_proceso']}\n";
                    $description .= "Clase: {$info['clase_proceso']}\n";
                    $description .= "Subclase: {$info['subclase']}\n";
                    $description .= "Departamento: {$info['departamento']}\n";
                    $description .= "Ciudad: {$info['ciudad']}\n";
                    $description .= "Corporacion: {$info['corporacion']}\n";
                    $description .= "Especialidad: {$info['especialidad']}\n";
                    $description .= "Distrito/Circuito: {$info['distrito_circuito']}\n";

                    if (! empty($info['sujetos'])) {
                        $description .= "\nSujetos Procesales:\n";
                        foreach ($info['sujetos'] as $s) {
                            $description .= "- {$s['rol']}: {$s['nombre']} ({$s['documento']})\n";
                        }
                    }

                    // Parsear fecha
                    $startedAt = null;
                    if ($info['fecha_publicacion']) {
                        foreach (['d/m/Y', 'j/m/Y', 'Y-m-d'] as $fmt) {
                            try {
                                $startedAt = Carbon::createFromFormat($fmt, trim($info['fecha_publicacion']));

                                break;
                            } catch (\Exception) {
                                continue;
                            }
                        }
                    }

                    $case = LegalCase::create([
                        'firm_id' => auth()->user()->firm_id,
                        'case_number' => $caseNumber,
                        'external_case_number' => $info['codigo_proceso'],
                        'title' => $title,
                        'description' => $description,
                        'case_type_id' => $caseType->id,
                        'client_id' => $data['client_id'],
                        'user_id' => $data['user_id'],
                        'status' => 'abierto',
                        'priority' => 'media',
                        'court' => $info['despacho'],
                        'opposing_party' => $demandados ?: $demandantes,
                        'started_at' => $startedAt,
                    ]);

                    Notification::make()
                        ->title('Proceso importado exitosamente')
                        ->body("Caso {$caseNumber} creado con la informacion de Tyba. Radicado: {$info['codigo_proceso']}")
                        ->success()
                        ->persistent()
                        ->send();

                    $this->redirect(LegalCaseResource::getUrl('view', ['record' => $case]));
                }),
            CreateAction::make(),
        ];
    }
}
