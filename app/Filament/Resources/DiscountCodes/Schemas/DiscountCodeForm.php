<?php

namespace App\Filament\Resources\DiscountCodes\Schemas;

use App\Models\DiscountCode;
use App\Models\Plan;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DiscountCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Codigo')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('code')
                            ->label('Codigo')
                            ->required()
                            ->maxLength(40)
                            ->placeholder('Ej: BIENVENIDA20, REFERIDO2026')
                            ->helperText('Sin espacios. Se convertira a mayusculas al guardar.')
                            ->dehydrateStateUsing(fn ($state) => strtoupper(trim($state))),
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->helperText('Solo los codigos activos pueden ser usados.'),
                        Textarea::make('description')
                            ->label('Descripcion interna')
                            ->rows(2)
                            ->placeholder('Para que se usa este codigo (uso interno)')
                            ->columnSpanFull(),
                    ]),

                Section::make('Descuento')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('type')
                            ->label('Tipo')
                            ->options(DiscountCode::TYPES)
                            ->default(DiscountCode::TYPE_PERCENT)
                            ->required()
                            ->live()
                            ->native(false),
                        TextInput::make('amount')
                            ->label('Valor')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->helperText(fn ($get) => $get('type') === DiscountCode::TYPE_PERCENT
                                ? 'Porcentaje (1 a 100)'
                                : 'Monto en COP que se resta del precio'),
                        Select::make('applies_to_plan_id')
                            ->label('Aplica solo al plan')
                            ->placeholder('Cualquier plan pago')
                            ->options(fn () => Plan::where('price_monthly', '>', 0)->pluck('name', 'id'))
                            ->searchable()
                            ->columnSpanFull(),
                    ]),

                Section::make('Vigencia y limites')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        DateTimePicker::make('valid_from')
                            ->label('Valido desde')
                            ->seconds(false)
                            ->placeholder('Inmediatamente')
                            ->helperText('Dejelo vacio para que sea valido desde ya.'),
                        DateTimePicker::make('valid_until')
                            ->label('Valido hasta')
                            ->seconds(false)
                            ->placeholder('Sin expiracion')
                            ->helperText('Dejelo vacio para que no expire por fecha.'),
                        TextInput::make('max_uses')
                            ->label('Maximo de usos')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Ilimitado')
                            ->helperText('Cuantas veces se puede canjear en total. Vacio = ilimitado.'),
                        TextInput::make('current_uses')
                            ->label('Usos actuales')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Se incrementa automaticamente cada vez que alguien usa el codigo.'),
                    ]),
            ]);
    }
}
