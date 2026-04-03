<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('firm_id')
                    ->label('Firma')
                    ->relationship('firm', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->required(),
                Select::make('role')
                    ->label('Rol')
                    ->options([
                        'superadmin' => 'Super Admin',
                        'admin' => 'Administrador',
                        'abogado' => 'Abogado',
                        'asistente' => 'Asistente',
                    ])
                    ->required()
                    ->default('abogado'),
                TextInput::make('password')
                    ->label('Contrasena')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),
            ]);
    }
}
