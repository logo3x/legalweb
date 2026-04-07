<?php

namespace App\Filament\Resources\LegalCases\RelationManagers;

use App\Notifications\CaseUpdatedNotification;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Actuaciones';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Título')
                    ->required(),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                DateTimePicker::make('event_date')
                    ->label('Fecha del Evento')
                    ->required(),
                Select::make('event_type')
                    ->label('Tipo')
                    ->options([
                        'actuacion' => 'Actuación',
                        'audiencia' => 'Audiencia',
                        'notificacion' => 'Notificación',
                        'memorial' => 'Memorial',
                        'auto' => 'Auto',
                        'sentencia' => 'Sentencia',
                    ])
                    ->required()
                    ->default('actuacion'),
                Toggle::make('is_milestone')
                    ->label('Hito importante'),
                Select::make('user_id')
                    ->label('Registrado por')
                    ->relationship('user', 'name', fn ($query) => $query->where('firm_id', auth()->user()->firm_id))
                    ->searchable()
                    ->preload(),
                Toggle::make('notify_client')
                    ->label('Notificar al cliente por email')
                    ->helperText('Se enviara un correo al cliente informando sobre esta actuacion')
                    ->default(false)
                    ->dehydrated(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('event_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->weight(fn ($record) => $record->created_at?->gt(now()->subHours(24)) ? 'bold' : null)
                    ->icon(fn ($record) => $record->created_at?->gt(now()->subHours(24)) ? 'heroicon-s-sparkles' : null)
                    ->iconColor('warning')
                    ->tooltip(fn ($record) => $record->created_at?->gt(now()->subHours(24)) ? 'Nueva - detectada en ultima sincronizacion' : null),
                TextColumn::make('event_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'audiencia' => 'warning',
                        'sentencia' => 'success',
                        'notificacion' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('description')
                    ->label('Detalle')
                    ->limit(40)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_milestone')
                    ->label('Hito')
                    ->boolean(),
                TextColumn::make('user.name')
                    ->label('Registrado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('event_date', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva Actuación')
                    ->after(function ($record, array $data) {
                        if (! ($data['notify_client'] ?? false)) {
                            return;
                        }

                        $case = $record->legalCase;
                        $client = $case->client;

                        if ($client?->email) {
                            $client->notify(new CaseUpdatedNotification($case, $record));

                            Notification::make()
                                ->title('Cliente notificado')
                                ->body("Se envio email a {$client->email}")
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
