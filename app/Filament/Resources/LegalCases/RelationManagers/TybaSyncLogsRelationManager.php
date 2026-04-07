<?php

namespace App\Filament\Resources\LegalCases\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TybaSyncLogsRelationManager extends RelationManager
{
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
                        'ok' => 'Nuevas actuaciones',
                        'error' => 'Error',
                        'sin_cambios' => 'Sin cambios',
                        default => $state,
                    }),
                TextColumn::make('nuevas_actuaciones')
                    ->label('Nuevas')
                    ->alignCenter(),
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
            ->defaultSort('created_at', 'desc')
            ->paginated([10]);
    }
}
