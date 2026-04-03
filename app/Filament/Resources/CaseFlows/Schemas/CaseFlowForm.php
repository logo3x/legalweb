<?php

namespace App\Filament\Resources\CaseFlows\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CaseFlowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('case_type_id')
                    ->label('Tipo de Proceso')
                    ->relationship('caseType', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->label('Nombre del Flujo')
                    ->required(),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
                Repeater::make('steps')
                    ->label('Pasos del Flujo')
                    ->relationship()
                    ->orderColumn('order')
                    ->reorderable()
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre del Paso')
                            ->required(),
                        Textarea::make('description')
                            ->label('Descripción'),
                        TextInput::make('days_limit')
                            ->label('Días Límite')
                            ->numeric()
                            ->minValue(1),
                        Toggle::make('is_required')
                            ->label('Obligatorio')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
