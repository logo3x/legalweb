<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Models\Client;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                Toggle::make('whatsapp_enabled')
                    ->label('El cliente autorizó recibir avisos por WhatsApp')
                    ->helperText(fn (?Client $record): string => 'Active esta opción solo si el cliente le dio su autorización (Ley 1581 de 2012). '
                        .'Los avisos (documentos solicitados, enlace del portal, reporte mensual) se envían al teléfono del cliente.'
                        .($record?->whatsapp_enabled && $record->whatsapp_consent_at
                            ? ' Autorización registrada el '.$record->whatsapp_consent_at->format('d/m/Y H:i').'.'
                            : ''))
                    ->columnSpanFull(),
                Toggle::make('process_monitoring_enabled')
                    ->label('Vigilar procesos nuevos de este cliente')
                    ->helperText('Cada noche buscamos el documento en la Rama Judicial y le avisamos si aparece en un proceso que aun no esta en LegalWeb.')
                    ->columnSpanFull(),
                Select::make('user_id')
                    ->label('Abogado Asignado')
                    ->relationship('user', 'name', fn ($query) => $query->where('firm_id', auth()->user()->firm_id))
                    ->searchable()
                    ->preload(),
            ]);
    }
}
