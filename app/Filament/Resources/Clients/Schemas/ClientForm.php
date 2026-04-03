<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('document_type')
                    ->label('Tipo de Documento')
                    ->options([
                        'CC' => 'Cédula de Ciudadanía',
                        'CE' => 'Cédula de Extranjería',
                        'NIT' => 'NIT',
                        'PP' => 'Pasaporte',
                        'TI' => 'Tarjeta de Identidad',
                    ])
                    ->required()
                    ->default('CC'),
                TextInput::make('document_number')
                    ->label('Número de Documento')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(20),
                TextInput::make('first_name')
                    ->label('Nombres')
                    ->required()
                    ->maxLength(100),
                TextInput::make('last_name')
                    ->label('Apellidos')
                    ->required()
                    ->maxLength(100),
                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->maxLength(20),
                TextInput::make('address')
                    ->label('Dirección'),
                TextInput::make('city')
                    ->label('Ciudad')
                    ->maxLength(100),
                Textarea::make('notes')
                    ->label('Notas')
                    ->columnSpanFull(),
                Select::make('user_id')
                    ->label('Abogado Asignado')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
