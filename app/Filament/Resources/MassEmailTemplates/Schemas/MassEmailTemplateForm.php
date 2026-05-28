<?php

namespace App\Filament\Resources\MassEmailTemplates\Schemas;

use App\Models\MassEmailTemplate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MassEmailTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nombre interno')
                    ->required()
                    ->maxLength(120)
                    ->placeholder('Ej: Bienvenida a nuevos usuarios')
                    ->helperText('Solo lo ve usted al elegir la plantilla. No se envia al destinatario.'),
                Select::make('category')
                    ->label('Categoria')
                    ->options(MassEmailTemplate::CATEGORIES)
                    ->default('general')
                    ->required()
                    ->native(false),
                TextInput::make('subject')
                    ->label('Asunto')
                    ->required()
                    ->maxLength(180)
                    ->columnSpanFull()
                    ->placeholder('Ej: Bienvenido a LegalWeb - tres pasos para empezar'),
                Textarea::make('body')
                    ->label('Cuerpo del mensaje')
                    ->required()
                    ->rows(10)
                    ->columnSpanFull()
                    ->helperText('Use {{name}}, {{email}} y {{firm}} para personalizar por destinatario. Separe los parrafos con una linea en blanco.'),
                Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true)
                    ->helperText('Solo las plantillas activas aparecen en el compositor de campanas.'),
            ]);
    }
}
