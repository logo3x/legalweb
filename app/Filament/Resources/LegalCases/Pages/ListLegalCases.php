<?php

namespace App\Filament\Resources\LegalCases\Pages;

use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\TybaParserService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Actions\Action as FormAction;
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
                ->modalWidth('2xl')
                ->modalHeading('Importar Proceso desde Rama Judicial')
                ->modalSubmitActionLabel('Crear Caso')
                ->form([
                    TextInput::make('radicado')
                        ->label('Codigo de Proceso (Radicado)')
                        ->required()
                        ->minLength(20)
                        ->maxLength(50)
                        ->placeholder('Ej: 68081310300120240001800')
                        ->helperText('Ingrese el radicado y haga click en "Abrir en Tyba" para ver los datos.')
                        ->suffixAction(
                            FormAction::make('open_tyba')
                                ->icon('heroicon-o-arrow-top-right-on-square')
                                ->label('Abrir en Tyba')
                                ->url(fn ($get) => $get('radicado')
                                    ? 'https://procesojudicial.ramajudicial.gov.co/Justicia21/Administracion/Ciudadanos/frmConsultaProceso.aspx?IdProceso='.preg_replace('/[^0-9]/', '', $get('radicado'))
                                    : null)
                                ->openUrlInNewTab()
                        ),
                    Textarea::make('tyba_data')
                        ->label('Datos de Tyba')
                        ->helperText('En la pagina de Tyba, seleccione todo (Ctrl+A), copie (Ctrl+C) y pegue aqui (Ctrl+V).')
                        ->placeholder("Pegue aqui el contenido copiado de la pagina de Tyba...\n\nEl sistema extraera automaticamente:\n- Informacion del proceso\n- Despacho y juzgado\n- Partes involucradas\n- Actuaciones")
                        ->rows(6)
                        ->columnSpanFull(),
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

                    // Parsear datos pegados de Tyba
                    $info = null;
                    if (! empty($data['tyba_data'])) {
                        $info = app(TybaParserService::class)->parseText($data['tyba_data']);
                    }

                    // Construir datos del caso
                    $title = 'Proceso Judicial - '.$radicado;
                    $court = null;
                    $opposingParty = null;
                    $description = null;
                    $caseTypeId = CaseType::first()?->id;

                    if ($info) {
                        $title = ($info['clase_proceso'] ?: 'Proceso Judicial').' - '.$radicado;
                        $court = $info['despacho'];
                        $caseTypeId = CaseType::firstOrCreate(['name' => $info['especialidad'] ?: 'General'])->id;
                        $opposingParty = $info['contraparte'];

                        if ($info['demandantes'] && $info['demandados']) {
                            $title = ($info['clase_proceso'] ?: 'Proceso').' - '.mb_substr($info['demandantes'], 0, 40).' vs '.mb_substr($info['demandados'], 0, 40);
                        }

                        $description = "Importado desde Tyba\n";
                        foreach (['tipo_proceso', 'clase_proceso', 'subclase', 'departamento', 'ciudad', 'corporacion', 'especialidad', 'despacho', 'direccion', 'telefono', 'email'] as $field) {
                            if (! empty($info[$field])) {
                                $label = str_replace('_', ' ', ucfirst($field));
                                $description .= "{$label}: {$info[$field]}\n";
                            }
                        }
                        if (! empty($info['sujetos_text'])) {
                            $description .= "\nSujetos Procesales:\n{$info['sujetos_text']}";
                        }
                        if (! empty($info['actuaciones_text'])) {
                            $description .= "\nActuaciones:\n{$info['actuaciones_text']}";
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

                    $msg = $info
                        ? "Caso {$caseNumber} creado con datos importados de Tyba."
                        : "Caso {$caseNumber} creado con radicado {$radicado}. Complete la informacion manualmente.";

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
