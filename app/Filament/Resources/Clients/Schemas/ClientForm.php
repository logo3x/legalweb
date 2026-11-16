<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Models\Client;
use App\Services\WhatsAppService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        // Datos del cliente a la izquierda (2/3); abogado, avisos y vigilancia a la derecha.
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Group::make([
                            Section::make('Datos del cliente')
                                ->description('Identificación y datos de contacto.')
                                ->icon(Heroicon::OutlinedIdentification)
                                ->columns(['default' => 1, 'sm' => 2])
                                ->schema([
                                    ...self::basicFields(),
                                    TextInput::make('address')
                                        ->label('Dirección'),
                                    TextInput::make('city')
                                        ->label('Ciudad')
                                        ->maxLength(100),
                                ]),
                            Section::make('Notas')
                                ->icon(Heroicon::OutlinedPencilSquare)
                                ->collapsible()
                                ->schema([
                                    Textarea::make('notes')
                                        ->label('Notas')
                                        ->hiddenLabel()
                                        ->rows(4)
                                        ->columnSpanFull(),
                                ]),
                        ])->columnSpan(['default' => 1, 'lg' => 2]),

                        Section::make('Seguimiento')
                            ->icon(Heroicon::OutlinedBellAlert)
                            ->schema([
                                Select::make('user_id')
                                    ->label('Abogado asignado')
                                    ->relationship('user', 'name', fn ($query) => $query->where('firm_id', auth()->user()->firm_id))
                                    ->searchable()
                                    ->preload(),
                                Toggle::make('process_monitoring_enabled')
                                    ->label('Vigilar procesos nuevos de este cliente')
                                    ->disabled(fn (): bool => ! self::firmHasMonitoring())
                                    ->helperText(fn (): string => self::firmHasMonitoring()
                                        ? 'Cada noche buscamos al cliente en la Rama Judicial (por su nombre, porque la Rama ya no permite buscar por cédula) y le avisamos si aparece en un proceso que aún no está en LegalWeb.'
                                        : 'La vigilancia de clientes esta incluida en los planes Profesional y Firma. Mejore su plan en "Mi Plan".')
                                    ->columnSpanFull(),
                                Toggle::make('whatsapp_enabled')
                                    ->visible(fn (): bool => WhatsAppService::isEnabled())
                                    ->label('El cliente autorizó recibir avisos por WhatsApp')
                                    ->hint(fn (): ?string => auth()->user()?->firm?->planLimits()->hasWhatsApp() === false ? 'Incluido en los planes Profesional y Firma' : null)
                                    ->helperText(fn (?Client $record): string => 'Active esta opción solo si el cliente le dio su autorización (Ley 1581 de 2012). '
                                        .'Los avisos (documentos solicitados, enlace del portal, reporte mensual) se envían al teléfono del cliente.'
                                        .($record?->whatsapp_enabled && $record->whatsapp_consent_at
                                            ? ' Autorización registrada el '.$record->whatsapp_consent_at->format('d/m/Y H:i').'.'
                                            : ''))
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(['default' => 1, 'lg' => 1]),
                    ]),
            ]);
    }

    private static function firmHasMonitoring(): bool
    {
        $firm = auth()->user()?->firm;

        return ! $firm || $firm->planLimits()->hasClientMonitoring();
    }

    /**
     * Datos esenciales del cliente; tambien se usan para crearlo desde el formulario del caso.
     *
     * @return array<int, Component>
     */
    public static function basicFields(): array
    {
        return [
            Select::make('document_type')
                ->label('Tipo de documento')
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
                ->label('Número de documento')
                ->required()
                // Unico dentro de la firma (otra firma puede tener el mismo cliente).
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('firm_id', auth()->user()?->firm_id))
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
                ->label('Correo electrónico')
                ->email()
                ->maxLength(255),
            TextInput::make('phone')
                ->label('Teléfono')
                ->tel()
                ->maxLength(20),
        ];
    }
}
