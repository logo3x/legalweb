<?php

namespace App\Filament\Resources\CaseTypes\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CaseTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(100),
                ColorPicker::make('color')
                    ->label('Color')
                    ->required()
                    ->default('#3B82F6'),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
            ]);
    }
}
