<?php

namespace App\Filament\Resources\LegalCases\Pages;

use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\TybaService;
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
                ->modalDescription('Ingrese el radicado judicial. El sistema intentara importar la informacion automaticamente desde Tyba.')
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

                    // Intentar importar datos de Tyba
                    $tyba = app(TybaService::class);
                    $info = $tyba->extractProcessInfo($radicado);

                    // Datos del proceso (de Tyba o defaults)
                    $title = 'Proceso Judicial';
                    $court = null;
                    $opposingParty = null;
                    $description = null;
                    $caseTypeId = CaseType::first()?->id;

                    if ($info && ! empty($info['despacho'])) {
                        $title = ($info['clase_proceso'] ?: 'Proceso Judicial').' - '.$radicado;
                        $court = $info['despacho'];
                        $caseTypeId = CaseType::firstOrCreate(['name' => $info['especialidad'] ?: 'General'])->id;

                        // Sujetos
                        $demandados = collect($info['sujetos'])->filter(fn ($s) => str_contains(strtolower($s['rol']), 'demandado'))->pluck('nombre')->join(', ');
                        $demandantes = collect($info['sujetos'])->filter(fn ($s) => str_contains(strtolower($s['rol']), 'demandante'))->pluck('nombre')->join(', ');
                        $opposingParty = $demandados ?: $demandantes;

                        if ($demandantes && $demandados) {
                            $title = ($info['clase_proceso'] ?: 'Proceso').' - '.mb_substr($demandantes, 0, 40).' vs '.mb_substr($demandados, 0, 40);
                        }

                        $description = "Importado desde Tyba\n";
                        $description .= "Tipo: {$info['tipo_proceso']}\n";
                        $description .= "Clase: {$info['clase_proceso']}\n";
                        $description .= "Departamento: {$info['departamento']} - {$info['ciudad']}\n";
                        $description .= "Despacho: {$info['despacho']}\n";
                        if ($info['email']) {
                            $description .= "Email: {$info['email']}\n";
                        }
                        if (! empty($info['sujetos'])) {
                            $description .= "\nSujetos:\n";
                            foreach ($info['sujetos'] as $s) {
                                $description .= "- {$s['rol']}: {$s['nombre']}\n";
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
                            'opposing_party' => $opposingParty,
                        ]);
                    } catch (QueryException) {
                        Notification::make()
                            ->title('Error al crear caso')
                            ->body('No se pudo crear el caso. Intente nuevamente.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $msg = $info && ! empty($info['despacho'])
                        ? "Caso {$caseNumber} creado con datos importados de Tyba."
                        : "Caso {$caseNumber} creado. No se pudieron importar datos de Tyba, complete la informacion manualmente.";

                    Notification::make()
                        ->title('Caso creado')
                        ->body($msg)
                        ->success()
                        ->persistent()
                        ->send();

                    $this->redirect(LegalCaseResource::getUrl('view', ['record' => $case]));
                }),
            CreateAction::make(),
        ];
    }
}
