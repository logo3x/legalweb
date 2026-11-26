<?php

namespace App\Filament\Resources\LegalCases\RelationManagers;

use App\Filament\Resources\LegalCases\RelationManagers\Concerns\ChecksCasePermissions;
use App\Notifications\FlowStepCompletedNotification;
use App\Services\JudicialCalendarService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FlowProgressRelationManager extends RelationManager
{
    use ChecksCasePermissions;

    protected static function casePermissionFor(string $action): ?string
    {
        return match ($action) {
            'viewAny', 'view' => 'flow.view',
            default => 'flow.manage',
        };
    }

    protected static string $relationship = 'flowProgress';

    protected static ?string $title = 'Flujo de Proceso';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('El flujo de proceso muestra las etapas legales de su caso. Cada paso tiene un plazo en días hábiles que empieza a correr cuando lo inicia (botón Iniciar); le avisamos 3 días hábiles antes, 1 día antes y el día del vencimiento. Los plazos se calculan con el calendario judicial colombiano.')
            ->recordTitleAttribute('flowStep.name')
            ->columns([
                TextColumn::make('flowStep.order')
                    ->label('#')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('flowStep.name')
                    ->label('Paso')
                    ->searchable(),
                TextColumn::make('flowStep.days_limit')
                    ->label('Plazo (días hábiles)')
                    ->placeholder('Sin límite')
                    ->alignCenter(),
                // Vencimiento del paso iniciado: dias habiles desde su inicio, con festivos y vacancia.
                TextColumn::make('started_at')
                    ->label('Vence')
                    ->state(function ($record): ?string {
                        if ($record->status !== 'en_progreso' || ! $record->started_at || ! $record->flowStep?->days_limit) {
                            return null;
                        }

                        return app(JudicialCalendarService::class)
                            ->calculateDeadline($record->started_at, (int) $record->flowStep->days_limit)['deadline']
                            ->format('d/m/Y');
                    })
                    ->placeholder('—')
                    ->tooltip('El plazo corre desde que se inicia el paso, en días hábiles del calendario judicial.'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completado' => 'success',
                        'en_progreso' => 'warning',
                        'pendiente' => 'gray',
                        'omitido' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completado' => 'Completado',
                        'en_progreso' => 'En Progreso',
                        'pendiente' => 'Pendiente',
                        'omitido' => 'Omitido',
                        default => $state,
                    }),
                TextColumn::make('completed_at')
                    ->label('Completado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
                TextColumn::make('completedByUser.name')
                    ->label('Por')
                    ->placeholder('-'),
                TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(30)
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->defaultSort('flowStep.order', 'asc')
            ->paginated(false)
            ->recordActions([
                Action::make('completar')
                    ->authorize(fn (): bool => $this->canOnCase('update'))
                    ->label('Completar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Textarea::make('notes')
                            ->label('Notas (opcional)'),
                        Toggle::make('notify_client')
                            ->label('Notificar al cliente por email')
                            ->helperText('Se informara al cliente que esta etapa fue completada')
                            ->default(false),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'status' => 'completado',
                            'completed_at' => now(),
                            'completed_by' => auth()->id(),
                            'notes' => $data['notes'] ?? null,
                        ]);

                        if (! ($data['notify_client'] ?? false)) {
                            return;
                        }

                        $case = $record->legalCase;
                        $client = $case->client;
                        $nextStep = $case->flowProgress()
                            ->whereIn('status', ['pendiente', 'en_progreso'])
                            ->join('flow_steps', 'flow_steps.id', '=', 'case_flow_progress.flow_step_id')
                            ->orderBy('flow_steps.order')
                            ->first();

                        if ($client?->email) {
                            $client->notify(new FlowStepCompletedNotification(
                                $case,
                                $record,
                                $nextStep?->flowStep?->name
                            ));

                            Notification::make()
                                ->title('Cliente notificado')
                                ->body("Se envio email a {$client->email}")
                                ->success()
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => in_array($record->status, ['pendiente', 'en_progreso']))
                    ->requiresConfirmation()
                    ->modalHeading('Completar paso')
                    ->modalDescription(fn ($record) => "¿Marcar \"{$record->flowStep->name}\" como completado?"),
                Action::make('en_progreso')
                    ->authorize(fn (): bool => $this->canOnCase('update'))
                    ->label('Iniciar')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->action(function ($record): void {
                        $record->update(['status' => 'en_progreso']);
                    })
                    ->visible(fn ($record) => $record->status === 'pendiente'),
                Action::make('omitir')
                    ->authorize(fn (): bool => $this->canOnCase('update'))
                    ->label('Omitir')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->form([
                        Textarea::make('notes')
                            ->label('Razón')
                            ->required(),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'status' => 'omitido',
                            'notes' => $data['notes'],
                        ]);
                    })
                    ->visible(fn ($record) => in_array($record->status, ['pendiente', 'en_progreso']))
                    ->requiresConfirmation(),
            ]);
    }
}
