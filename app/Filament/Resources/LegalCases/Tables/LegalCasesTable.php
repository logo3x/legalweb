<?php

namespace App\Filament\Resources\LegalCases\Tables;

use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\LegalCase;
use App\Services\CaseSyncService;
use App\Services\UsageLimiter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class LegalCasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->modifyQueryUsing(fn ($query) => $query->with('client')->withMax('events', 'event_date'))
            ->recordUrl(fn (LegalCase $record): string => LegalCaseResource::getUrl('view', ['record' => $record]))
            ->columns([
                // Numero interno arriba y radicado debajo: es lo que el abogado reconoce.
                TextColumn::make('case_number')
                    ->label('Caso')
                    ->searchable(['case_number', 'external_case_number'])
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (LegalCase $record): ?string => $record->is_demo ? 'Ejemplo' : $record->external_case_number)
                    ->color(fn (LegalCase $record): ?string => $record->is_demo ? 'gray' : null),
                TextColumn::make('title')
                    ->label('Proceso')
                    ->searchable()
                    ->limit(90)
                    ->wrap()
                    ->grow()
                    ->extraAttributes(['style' => 'min-width: 16rem'])
                    ->description(fn (LegalCase $record): string => trim(($record->client?->first_name ?? '').' '.($record->client?->last_name ?? ''))),
                // La ultima consulta a la Rama Judicial / SAMAI fallo: el caso no esta al dia.
                TextColumn::make('sync_failed_at')
                    ->label('Consulta')
                    ->state(fn (LegalCase $record): ?string => match (true) {
                        $record->sync_failed_at !== null => 'Con error',
                        blank($record->external_case_number) => null,
                        default => 'Al día',
                    })
                    ->color(fn (?string $state): string => $state === 'Con error' ? 'danger' : 'gray')
                    ->weight(fn (?string $state): ?string => $state === 'Con error' ? 'medium' : null)
                    ->icon(fn (?string $state): ?string => $state === 'Con error' ? 'heroicon-m-exclamation-triangle' : null)
                    ->tooltip(fn (LegalCase $record): ?string => $record->sync_error)
                    ->placeholder('Sin radicado'),
                TextColumn::make('events_max_event_date')
                    ->label('Última actuación')
                    ->since()
                    ->dateTimeTooltip('d/m/Y')
                    ->sortable()
                    ->color('gray')
                    ->placeholder('Sin actuaciones'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'en_espera' ? 'warning' : 'gray')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'abierto' => 'Abierto',
                        'en_progreso' => 'En progreso',
                        'en_espera' => 'En espera',
                        'cerrado' => 'Cerrado',
                        'archivado' => 'Archivado',
                        default => ucfirst($state),
                    }),
                // Solo se marca lo que pide atencion; baja y media no compiten por la mirada.
                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->state(fn (LegalCase $record): ?string => in_array($record->priority, ['alta', 'urgente'], true) ? $record->priority : null)
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (?string $state): string => $state === 'urgente' ? 'danger' : 'warning')
                    ->placeholder('')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('caseType.name')
                    ->label('Tipo')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('client.first_name')
                    ->label('Cliente')
                    ->formatStateUsing(fn (LegalCase $record): string => "{$record->client->first_name} {$record->client->last_name}")
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // En firmas con un solo abogado la columna siempre diria lo mismo.
                TextColumn::make('user.name')
                    ->label('Abogado')
                    ->sortable()
                    ->visible(fn (): bool => self::firmHasSeveralLawyers())
                    ->toggleable()
                    ->visibleFrom('lg'),
                TextColumn::make('started_at')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('sync_failed')
                    ->label('Con error de consulta')
                    ->toggle()
                    ->query(fn ($query) => $query->whereNotNull('sync_failed_at')),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'abierto' => 'Abierto',
                        'en_progreso' => 'En progreso',
                        'en_espera' => 'En espera',
                        'cerrado' => 'Cerrado',
                        'archivado' => 'Archivado',
                    ]),
                SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'baja' => 'Baja',
                        'media' => 'Media',
                        'alta' => 'Alta',
                        'urgente' => 'Urgente',
                    ]),
                SelectFilter::make('case_type_id')
                    ->label('Tipo de proceso')
                    ->relationship('caseType', 'name'),
                TrashedFilter::make(),
            ])
            // Al hacer clic en la fila se abre el caso; lo demas queda en un solo menu.
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Editar'),
                    // Atajo para no tener que entrar al caso a sincronizar.
                    Action::make('quick_sync')
                        ->label('Sincronizar')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->visible(fn (LegalCase $record): bool => filled($record->external_case_number) && ! $record->trashed())
                        ->authorize(fn (LegalCase $record): bool => (bool) auth()->user()?->hasCasePermission($record->getKey(), 'events.create'))
                        ->action(function (LegalCase $record): void {
                            if (! UsageLimiter::attempt('judicial')) {
                                return;
                            }

                            $log = app(CaseSyncService::class)->sync($record, 'manual', notify: false);

                            $notification = Notification::make()->title($record->case_number)->body($log->mensaje);

                            match ($log->status) {
                                'error' => $notification->danger()->send(),
                                'ok' => $notification->success()->send(),
                                default => $notification->info()->send(),
                            };
                        }),
                ])
                    ->tooltip('Más acciones'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->isAdmin())
                        ->modalHeading('Eliminar casos seleccionados')
                        ->modalDescription('Los casos seleccionados se moveran a la papelera. Podra restaurarlos despues.'),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->isAdmin())
                        ->modalHeading('Eliminar permanentemente')
                        ->modalDescription('ADVERTENCIA IRREVERSIBLE: Se eliminaran permanentemente todos los casos seleccionados con sus documentos, actuaciones y registros. Esta accion NO se puede deshacer.'),
                    RestoreBulkAction::make()
                        ->visible(fn () => auth()->user()->isAdmin()),
                ]),
            ]);
    }

    /**
     * Si la firma tiene mas de un abogado vale la pena ver quien lleva cada caso.
     */
    private static function firmHasSeveralLawyers(): bool
    {
        static $cache = new \WeakMap;
        $user = auth()->user();

        if (! $user?->firm) {
            return false;
        }

        return $cache[$user->firm] ??= $user->firm->users()->count() > 1;
    }
}
