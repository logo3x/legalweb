<?php

namespace App\Filament\Resources\LegalCases\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        TextInput::make('external_case_number')
                            ->label('Radicado Judicial')
                            ->maxLength(50),
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),
                        Select::make('case_type_id')
                            ->label('Tipo de Proceso')
                            ->relationship('caseType', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->label('Estado')
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
                            ->relationship('client', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} ({$record->document_number})")
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('user_id')
                            ->label('Abogado Responsable')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('opposing_party')
                            ->label('Contraparte')
                            ->columnSpanFull(),
                    ]),
                Section::make('Información Judicial')
                    ->columns(2)
                    ->schema([
                        TextInput::make('court')
                            ->label('Juzgado / Despacho'),
                        TextInput::make('judge')
                            ->label('Juez'),
                        DatePicker::make('started_at')
                            ->label('Fecha de Inicio'),
                        DatePicker::make('closed_at')
                            ->label('Fecha de Cierre'),
                    ]),
            ]);
    }
}
