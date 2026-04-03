<?php

namespace App\Filament\Resources\LegalCases\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
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
                    ->sortable(),
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
