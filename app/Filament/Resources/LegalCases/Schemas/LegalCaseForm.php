<?php

namespace App\Filament\Resources\LegalCases\Schemas;

use App\Models\CaseFlow;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LegalCaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Caso')
                    ->columns(2)
                    ->schema([
                        TextInput::make('case_number')
                            ->label('Número de Caso')
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Identificador interno unico del caso dentro de la plataforma.')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        TextInput::make('external_case_number')
                            ->label('Radicado Judicial')
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Numero de radicado asignado por el despacho judicial. Dejelo vacio si aun no ha sido radicado.')
                            ->maxLength(50),
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
                            ->label('Tipo de Proceso')
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Categoria del proceso: civil, laboral, penal, etc. Determina los flujos disponibles.')
                            ->relationship('caseType', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('case_flow_id')
                            ->label('Flujo de Proceso')
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Define las etapas procesales del caso. Al asignar un flujo se generan automaticamente los pasos a seguir. Puede configurar flujos en Configuracion > Flujos de Proceso.')
                            ->options(fn ($get) => CaseFlow::query()
                                ->where('case_type_id', $get('case_type_id'))
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder('Seleccionar flujo (opcional)'),
                        Select::make('status')
                            ->label('Estado')
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Estado actual del caso. Abierto: recien creado. En Progreso: en curso. En Espera: detenido temporalmente. Cerrado: finalizado.')
                            ->options([
                                'abierto' => 'Abierto',
                                'en_progreso' => 'En Progreso',
                                'en_espera' => 'En Espera',
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
                Section::make('Partes Involucradas')
                    ->columns(2)
                    ->schema([
                        Select::make('client_id')
                            ->label('Cliente')
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Persona natural o juridica que contrata los servicios del abogado.')
                            ->relationship('client', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} ({$record->document_number})")
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('user_id')
                            ->label('Abogado Responsable')
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
                Section::make('Información Judicial')
                    ->columns(2)
                    ->schema([
                        TextInput::make('court')
                            ->label('Juzgado / Despacho')
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Despacho judicial donde se tramita el proceso. Ej: "Juzgado 5 Civil del Circuito de Bogota".'),
                        TextInput::make('judge')
                            ->label('Juez')
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Nombre del juez o magistrado asignado al caso.'),
                        DatePicker::make('started_at')
                            ->label('Fecha de Inicio')
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Fecha en que se inicio el proceso o se presento la demanda.'),
                        DatePicker::make('closed_at')
                            ->label('Fecha de Cierre')
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Fecha en que se cerro o archivo el caso. Dejelo vacio si el caso sigue activo.'),
                    ]),
                Section::make('Documentos Iniciales')
                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Suba los documentos del caso como demanda, poder, anexos, etc. Puede agregar mas documentos despues.')
                    ->schema([
                        FileUpload::make('initial_documents')
                            ->label('Archivos')
                            ->multiple()
                            ->directory('documents')
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable()
                            ->maxFiles(10)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
