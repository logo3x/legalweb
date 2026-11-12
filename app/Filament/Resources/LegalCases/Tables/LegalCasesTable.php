<?php

namespace App\Filament\Resources\LegalCases\Tables;

use App\Models\LegalCase;
use App\Services\CaseSyncService;
use App\Services\UsageLimiter;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class LegalCasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('case_number')
                    ->label('No. Caso')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->is_demo ? 'Ejemplo' : null)
                    ->color(fn ($record) => $record->is_demo ? 'gray' : null),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('caseType.name')
                    ->label('Tipo')
                    ->sortable()
                    ->badge(),
                TextColumn::make('client.first_name')
                    ->label('Cliente')
                    ->formatStateUsing(fn ($record) => "{$record->client->first_name} {$record->client->last_name}")
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Abogado')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'abierto' => 'info',
                        'en_progreso' => 'warning',
                        'en_espera' => 'gray',
                        'cerrado' => 'success',
                        'archivado' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'baja' => 'gray',
                        'media' => 'info',
                        'alta' => 'warning',
                        'urgente' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('started_at')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'abierto' => 'Abierto',
                        'en_progreso' => 'En Progreso',
                        'en_espera' => 'En Espera',
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
                    ->label('Tipo de Proceso')
                    ->relationship('caseType', 'name'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Ver'),
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
}
