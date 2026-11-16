<?php

namespace App\Filament\Resources\LegalCases\Schemas;

use App\Filament\Resources\Clients\Schemas\ClientForm;
use App\Models\CaseFlow;
use App\Models\Client;
use App\Models\LegalCase;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class LegalCaseForm
{
    public static function configure(Schema $schema): Schema
    {
        // Dos columnas en pantallas grandes: los datos del caso y las partes a la izquierda (2/3);
        // estado, radicacion y fechas a la derecha. En el celular todo se apila.
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Group::make([
                            Section::make('Datos del caso')
                                ->description('Qué se discute y en qué tipo de proceso.')
                                ->icon(Heroicon::OutlinedBriefcase)
                                ->columns(['default' => 1, 'sm' => 2])
                                ->schema([
                                    TextInput::make('title')
                                        ->label('Título')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Nombre descriptivo del caso. Ej: "Demanda laboral contra Empresa XYZ".')
                                        ->required()
                                        ->columnSpanFull(),
                                    Textarea::make('description')
                                        ->label('Descripción')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Resumen general del caso, hechos relevantes y pretensiones.')
                                        ->columnSpanFull(),
                                    Select::make('case_type_id')
                                        ->label('Tipo de proceso')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Categoria del proceso: civil, laboral, penal, etc. Determina los flujos disponibles.')
                                        ->relationship('caseType', 'name')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->live(),
                                    Select::make('case_flow_id')
                                        ->label('Flujo de proceso')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Define las etapas procesales del caso. Al asignar un flujo se generan automaticamente los pasos a seguir. Puede configurar flujos en Configuracion > Flujos de Proceso.')
                                        ->options(fn ($get) => CaseFlow::query()
                                            ->where('case_type_id', $get('case_type_id'))
                                            ->where('is_active', true)
                                            ->pluck('name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->placeholder('Seleccionar flujo (opcional)'),
                                ]),
                            Section::make('Partes involucradas')
                                ->description('Cliente, abogado a cargo y contraparte.')
                                ->icon(Heroicon::OutlinedUserGroup)
                                ->columns(['default' => 1, 'sm' => 2])
                                ->schema([
                                    Select::make('client_id')
                                        ->label('Cliente')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Persona natural o juridica que contrata los servicios del abogado.')
                                        ->relationship('client', 'first_name')
                                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} ({$record->document_number})")
                                        ->required()
                                        ->searchable(['first_name', 'last_name', 'document_number'])
                                        ->preload()
                                        // Crear el cliente sin salir del formulario del caso.
                                        ->createOptionForm(ClientForm::basicFields())
                                        ->createOptionModalHeading('Nuevo cliente')
                                        ->createOptionUsing(fn (array $data): int => Client::create([
                                            ...$data,
                                            'firm_id' => auth()->user()->firm_id,
                                            'user_id' => auth()->id(),
                                        ])->getKey()),
                                    Select::make('user_id')
                                        ->label('Abogado responsable')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Abogado principal a cargo del caso.')
                                        ->relationship('user', 'name', fn ($query) => $query->where('firm_id', auth()->user()->firm_id))
                                        ->required()
                                        ->searchable()
                                        ->preload(),
                                    TextInput::make('opposing_party')
                                        ->label('Contraparte')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Nombre de la parte contraria en el proceso (demandado, demandante segun el caso).')
                                        ->columnSpanFull(),
                                ]),
                        ])->columnSpan(['default' => 1, 'lg' => 2]),

                        Group::make([
                            Section::make('Estado')
                                ->icon(Heroicon::OutlinedFlag)
                                ->schema([
                                    TextInput::make('case_number')
                                        ->label('Número de caso')
                                        ->default(fn (): string => LegalCase::nextCaseNumber())
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Identificador interno unico del caso dentro de la plataforma.')
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(50),
                                    Select::make('status')
                                        ->label('Estado')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Estado actual del caso. Abierto: recien creado. En Progreso: en curso. En Espera: detenido temporalmente. Cerrado: finalizado.')
                                        ->options([
                                            'abierto' => 'Abierto',
                                            'en_progreso' => 'En progreso',
                                            'en_espera' => 'En espera',
                                            'cerrado' => 'Cerrado',
                                            'archivado' => 'Archivado',
                                        ])
                                        ->required()
                                        ->default('abierto'),
                                    Select::make('priority')
                                        ->label('Prioridad')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Nivel de urgencia. Urgente: requiere atencion inmediata (terminos proximos a vencer).')
                                        ->options([
                                            'baja' => 'Baja',
                                            'media' => 'Media',
                                            'alta' => 'Alta',
                                            'urgente' => 'Urgente',
                                        ])
                                        ->required()
                                        ->default('media'),
                                ]),
                            Section::make('Radicación')
                                ->icon(Heroicon::OutlinedBuildingLibrary)
                                ->schema([
                                    TextInput::make('external_case_number')
                                        ->label('Radicado judicial')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Numero de radicado asignado por el despacho judicial. Dejelo vacio si aun no ha sido radicado.')
                                        ->maxLength(50)
                                        ->regex('/^[0-9\- ]*$/')
                                        ->validationMessages(['regex' => 'El radicado solo puede tener numeros, guiones y espacios.']),
                                    TextInput::make('court')
                                        ->label('Juzgado / Despacho')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Despacho judicial donde se tramita el proceso. Ej: "Juzgado 5 Civil del Circuito de Bogota".'),
                                    TextInput::make('judge')
                                        ->label('Juez')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Nombre del juez o magistrado asignado al caso.'),
                                ]),
                            Section::make('Fechas')
                                ->icon(Heroicon::OutlinedCalendarDays)
                                ->columns(['default' => 1, 'sm' => 2, 'lg' => 1, 'xl' => 2])
                                ->schema([
                                    DatePicker::make('started_at')
                                        ->label('Fecha de inicio')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Fecha en que se inicio el proceso o se presento la demanda.'),
                                    DatePicker::make('closed_at')
                                        ->label('Fecha de cierre')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Fecha en que se cerro o archivo el caso. Dejelo vacio si el caso sigue activo.'),
                                ]),
                        ])->columnSpan(['default' => 1, 'lg' => 1]),
                    ]),
                // Los documentos no se suben a LegalWeb: se registran como enlaces a almacenamiento
                // externo (Drive, OneDrive...) en la pestana Documentos del caso.
            ]);
    }
}
