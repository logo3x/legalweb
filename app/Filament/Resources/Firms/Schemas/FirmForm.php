<?php

namespace App\Filament\Resources\Firms\Schemas;

use App\Models\Firm;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class FirmForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Seguimiento')
                    ->description('Estado interno, notas y etiquetas de esta firma. Solo visible para superadmin.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        Select::make('tracking_status')
                            ->label('Estado')
                            ->options(Firm::TRACKING_STATUSES)
                            ->default('activo')
                            ->native(false)
                            ->required(),
                        TagsInput::make('tracking_tags')
                            ->label('Etiquetas')
                            ->placeholder('Agregar etiqueta y presionar Enter')
                            ->helperText('Use etiquetas libres: contactado, interesado, demo agendada, etc.'),
                        Textarea::make('tracking_notes')
                            ->label('Notas de seguimiento')
                            ->rows(4)
                            ->placeholder('Anote aqui el historial de contacto, observaciones, acuerdos, etc.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Datos de la Firma')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required(),
                        TextInput::make('nit')
                            ->label('NIT')
                            ->maxLength(20),
                        TextInput::make('legal_name')
                            ->label('Razon Social'),
                        TextInput::make('email')
                            ->label('Correo')
                            ->email(),
                        TextInput::make('phone')
                            ->label('Telefono')
                            ->tel(),
                        TextInput::make('city')
                            ->label('Ciudad'),
                        TextInput::make('department')
                            ->label('Departamento'),
                        TextInput::make('website')
                            ->label('Sitio Web')
                            ->url(),
                        TextInput::make('address')
                            ->label('Direccion')
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Descripcion')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),
                Section::make('Configuracion')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('logos')
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth('800')
                            ->imageResizeTargetHeight('400')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                            ->helperText(new HtmlString(
                                'PNG transparente, SVG, JPG o WebP &middot; horizontal (600&times;200) o cuadrado (400&times;400) &middot; max 2 MB &middot; recorte cerca del contenido.'
                            )),
                        Toggle::make('onboarding_completed')
                            ->label('Onboarding completado'),
                    ]),
            ]);
    }
}
