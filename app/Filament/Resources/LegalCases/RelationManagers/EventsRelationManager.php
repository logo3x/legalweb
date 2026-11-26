<?php

namespace App\Filament\Resources\LegalCases\RelationManagers;

use App\Filament\Resources\LegalCases\RelationManagers\Concerns\ChecksCasePermissions;
use App\Models\CaseEvent;
use App\Notifications\CaseUpdatedNotification;
use Filament\Actions\Action;
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
use Illuminate\Support\Facades\Log;

class EventsRelationManager extends RelationManager
{
    use ChecksCasePermissions;

    protected static function casePermissionFor(string $action): ?string
    {
        return match ($action) {
            'viewAny', 'view' => 'events.view',
            'create' => 'events.create',
            default => 'events.edit',
        };
    }

    protected static string $relationship = 'events';

    protected static ?string $title = 'Actuaciones';

    public function isReadOnly(): bool
    {
        return false;
    }

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
                    ->label('Nueva Actuación'),
            ])
            ->recordActions([
                // Accion inmediata con confirmacion: el abogado ve a que correo se envia y si salio.
                Action::make('notify_client')
                    ->label('Avisar al cliente')
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->visible(fn (): bool => $this->getOwnerRecord()->client !== null)
                    ->authorize(fn (): bool => static::userHasCasePermission($this->getOwnerRecord(), 'portal.share'))
                    ->disabled(fn (): bool => ! $this->clientCanReceiveEmail())
                    ->tooltip(fn (): ?string => $this->clientCanReceiveEmail() ? null : $this->clientEmailProblem())
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-o-envelope')
                    ->modalHeading('Avisar al cliente por correo')
                    ->modalDescription(fn (CaseEvent $record): string => 'Se enviará ahora a '.$this->getOwnerRecord()->client->email.' un correo con la actuación «'.$record->title.'» del '.$record->event_date->format('d/m/Y').'.')
                    ->modalSubmitActionLabel('Enviar correo')
                    ->action(function (CaseEvent $record): void {
                        $case = $this->getOwnerRecord();
                        $client = $case->client;

                        try {
                            $client->notifyNow(new CaseUpdatedNotification($case, $record));
                        } catch (\Throwable $e) {
                            Log::warning('Aviso de actuacion al cliente no enviado', ['event' => $record->id, 'error' => $e->getMessage()]);

                            Notification::make()
                                ->title('No se pudo enviar el correo')
                                ->body('Intente de nuevo en unos minutos. Si el problema sigue, revise el correo del cliente.')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Correo enviado')
                            ->body("El cliente recibirá el aviso en {$client->email}. Si no le llega, pídale revisar la carpeta de spam.")
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private function clientCanReceiveEmail(): bool
    {
        return $this->clientEmailProblem() === null;
    }

    /**
     * Por que no se le puede escribir al cliente, o null si se puede.
     */
    private function clientEmailProblem(): ?string
    {
        $client = $this->getOwnerRecord()->client;

        return match (true) {
            $client === null => 'El caso no tiene cliente.',
            $client->is_demo => 'Es un cliente de ejemplo: no se le envían correos.',
            blank($client->email) => 'El cliente no tiene correo. Agréguelo en Clientes para poder avisarle.',
            default => null,
        };
    }
}
