<?php

namespace App\Filament\Resources\MassEmailCampaigns\Schemas;

use App\Models\Firm;
use App\Models\MassEmailCampaign;
use App\Models\MassEmailTemplate;
use App\Models\Plan;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MassEmailCampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Mensaje')
                    ->columns(1)
                    ->schema([
                        Select::make('template_loader')
                            ->label('Cargar plantilla (opcional)')
                            ->placeholder('Seleccione una plantilla para precargar asunto y cuerpo')
                            ->options(fn () => MassEmailTemplate::where('is_active', true)
                                ->orderBy('category')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($t) => [$t->id => '['.(MassEmailTemplate::CATEGORIES[$t->category] ?? $t->category).'] '.$t->name])
                                ->toArray())
                            ->searchable()
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if (! $state) {
                                    return;
                                }
                                $tpl = MassEmailTemplate::find($state);
                                if ($tpl) {
                                    $set('subject', $tpl->subject);
                                    $set('body', $tpl->body);
                                }
                            })
                            ->helperText('Las plantillas se gestionan en Super Admin > Plantillas de correo. Cargarlas aqui sobrescribe lo que tenga en Asunto y Cuerpo.'),
                        TextInput::make('subject')
                            ->label('Asunto')
                            ->required()
                            ->maxLength(180)
                            ->placeholder('Ej: Novedades de LegalWeb - Abril 2026'),
                        Textarea::make('body')
                            ->label('Cuerpo del mensaje')
                            ->required()
                            ->rows(10)
                            ->placeholder("Escriba aqui el cuerpo del correo. Use parrafos separados por linea en blanco.\n\nVariables disponibles:\n- {{name}} (nombre del usuario)\n- {{email}} (correo del usuario)\n- {{firm}} (nombre de la firma)")
                            ->helperText('Soporta multiples parrafos. Variables {{name}}, {{email}}, {{firm}} se reemplazan automaticamente.'),
                    ]),

                Section::make('Audiencia')
                    ->columns(1)
                    ->schema([
                        Select::make('audience_type')
                            ->label('Enviar a')
                            ->options(MassEmailCampaign::AUDIENCE_TYPES)
                            ->default('all')
                            ->required()
                            ->live()
                            ->native(false),
                        Select::make('audience_filters.plans')
                            ->label('Planes')
                            ->multiple()
                            ->options(fn () => Plan::pluck('name', 'slug')->toArray())
                            ->visible(fn ($get) => $get('audience_type') === 'by_plan')
                            ->helperText('Solo usuarios cuyas firmas tienen una suscripcion activa en alguno de estos planes.'),
                        Select::make('audience_filters.statuses')
                            ->label('Estados de firma')
                            ->multiple()
                            ->options(Firm::TRACKING_STATUSES)
                            ->visible(fn ($get) => $get('audience_type') === 'by_status')
                            ->helperText('Solo usuarios de firmas con alguno de estos estados internos.'),
                        Select::make('audience_user_ids')
                            ->label('Usuarios especificos')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn () => User::with('firm')->orderBy('name')->get()->mapWithKeys(fn ($u) => [$u->id => $u->name.' - '.$u->email.($u->firm ? ' ('.$u->firm->name.')' : '')])->toArray())
                            ->visible(fn ($get) => $get('audience_type') === 'specific'),
                    ]),

                Section::make('Programacion')
                    ->columns(1)
                    ->schema([
                        Toggle::make('schedule_send')
                            ->label('Programar envio para mas tarde')
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn ($component, $record) => $component->state((bool) $record?->scheduled_at)),
                        DateTimePicker::make('scheduled_at')
                            ->label('Fecha y hora de envio')
                            ->seconds(false)
                            ->minDate(now())
                            ->visible(fn ($get) => $get('schedule_send')),
                    ]),
            ]);
    }
}
