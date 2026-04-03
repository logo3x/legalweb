<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('firm_id')
                    ->label('Firma')
                    ->relationship('firm', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('plan_id')
                    ->label('Plan')
                    ->relationship('plan', 'name')
                    ->required(),
                Select::make('billing_cycle')
                    ->label('Ciclo de Facturacion')
                    ->options([
                        'monthly' => 'Mensual',
                        'biannual' => 'Semestral',
                    ])
                    ->required()
                    ->default('monthly'),
                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activa',
                        'canceled' => 'Cancelada',
                        'expired' => 'Expirada',
                        'pending' => 'Pendiente de pago',
                    ])
                    ->required()
                    ->default('active'),
                DateTimePicker::make('starts_at')
                    ->label('Inicio')
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->label('Vencimiento'),
                DateTimePicker::make('trial_ends_at')
                    ->label('Fin de Prueba'),
                TextInput::make('wompi_reference')
                    ->label('Ref. Wompi'),
                TextInput::make('wompi_subscription_id')
                    ->label('ID Suscripcion Wompi'),
            ]);
    }
}
