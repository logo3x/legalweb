<?php

namespace App\Filament\Resources\LegalCases\RelationManagers;

use App\Filament\Resources\LegalCases\RelationManagers\Concerns\ChecksCasePermissions;
use App\Models\TybaSyncLog;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TybaSyncLogsRelationManager extends RelationManager
{
    use ChecksCasePermissions;

    protected static function casePermissionFor(string $action): ?string
    {
        return match ($action) {
            'viewAny', 'view' => 'events.view',
            default => 'events.edit',
        };
    }

    protected static string $relationship = 'tybaSyncLogs';

    protected static ?string $title = 'Historial de Sincronizacion';

    public function isPageBased(): bool
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
            ->description(fn () => 'Sincronizacion automatica diaria a las 3:00 AM. Notificaciones al correo del abogado ('.$this->getOwnerRecord()->user?->email.') cuando hay novedades. Las actuaciones importantes generan recordatorios en su agenda.')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ok' => 'success',
                        'error' => 'danger',
                        'sin_cambios' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ok' => 'Con novedades',
                        'error' => 'Error',
                        'sin_cambios' => 'Sin cambios',
                        default => $state,
                    }),
                TextColumn::make('nuevas_actuaciones')
                    ->label('Nuevas')
                    ->alignCenter()
                    ->color(fn (int $state): ?string => $state > 0 ? 'success' : null)
                    ->weight(fn (int $state): ?string => $state > 0 ? 'bold' : null),
                TextColumn::make('mensaje')
                    ->label('Detalle')
                    ->limit(60),
                TextColumn::make('origen')
                    ->label('Origen')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'manual' => 'info',
                        'automatico' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('ver_cambios')
                    ->label('Ver cambios')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->visible(fn (TybaSyncLog $record): bool => ! empty($record->cambios))
                    ->modalHeading(fn (TybaSyncLog $record): string => 'Cambios detectados el '.$record->created_at->format('d/m/Y H:i'))
                    ->modalContent(fn (TybaSyncLog $record) => view('filament.modals.sync-changes', ['cambios' => $record->cambios]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10]);
    }
}
