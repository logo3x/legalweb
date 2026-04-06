<?php

namespace App\Filament\Resources\LegalCases\Pages;

use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
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
                ->label('Importar Radicado')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->modalWidth('xl')
                ->modalHeading('Importar Proceso por Radicado')
                ->modalDescription('Cree un caso rapidamente a partir del radicado judicial.')
                ->modalSubmitActionLabel('Crear Caso')
                ->form([
                    TextInput::make('radicado')
                        ->label('Codigo de Proceso (Radicado)')
                        ->required()
                        ->minLength(20)
                        ->maxLength(50)
                        ->placeholder('Ej: 68081310300120240001800')
                        ->helperText('23 digitos del radicado asignado por la Rama Judicial.'),
                    TextInput::make('title')
                        ->label('Titulo del Caso')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Ej: Demanda laboral contra Empresa XYZ'),
                    Select::make('case_type_id')
                        ->label('Tipo de Proceso')
                        ->options(fn () => CaseType::pluck('name', 'id'))
                        ->required()
                        ->searchable(),
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
                    TextInput::make('court')
                        ->label('Juzgado / Despacho')
                        ->placeholder('Ej: Juzgado 1 Civil del Circuito'),
                    TextInput::make('opposing_party')
                        ->label('Contraparte')
                        ->placeholder('Nombre de la parte contraria'),
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
                            'title' => $data['title'],
                            'case_type_id' => $data['case_type_id'],
                            'client_id' => $data['client_id'],
                            'user_id' => $data['user_id'],
                            'status' => 'abierto',
                            'priority' => 'media',
                            'court' => $data['court'] ?? null,
                            'opposing_party' => $data['opposing_party'] ?? null,
                        ]);
                    } catch (QueryException) {
                        Notification::make()
                            ->title('Error al crear caso')
                            ->body('No se pudo crear el caso. Intente nuevamente.')
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Caso creado exitosamente')
                        ->body("Caso {$caseNumber} creado con radicado {$radicado}.")
                        ->success()
                        ->persistent()
                        ->send();

                    $this->redirect(LegalCaseResource::getUrl('view', ['record' => $case]));
                }),
            CreateAction::make(),
        ];
    }
}
