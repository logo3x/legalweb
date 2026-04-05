<?php

namespace App\Filament\Resources\Reminders\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RemindersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_completed')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock'),
                TextColumn::make('title')
                    ->label('Titulo')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'audiencia' => 'warning',
                        'vencimiento' => 'danger',
                        'reunion' => 'info',
                        'tarea' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'audiencia' => 'Audiencia',
                        'vencimiento' => 'Vencimiento',
                        'reunion' => 'Reunion',
                        'tarea' => 'Tarea',
                        default => 'Recordatorio',
                    }),
                TextColumn::make('due_date')
                    ->label('Fecha limite')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color(fn ($record) => ! $record->is_completed && $record->due_date->isPast() ? 'danger' : null),
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
                TextColumn::make('legalCase.case_number')
                    ->label('Caso')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->defaultSort('due_date', 'asc')
            ->filters([
                TernaryFilter::make('is_completed')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Completados')
                    ->falseLabel('Pendientes')
                    ->default(false),
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'audiencia' => 'Audiencia',
                        'vencimiento' => 'Vencimiento',
                        'reunion' => 'Reunion',
                        'tarea' => 'Tarea',
                        'recordatorio' => 'Recordatorio',
                    ]),
                SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'baja' => 'Baja',
                        'media' => 'Media',
                        'alta' => 'Alta',
                        'urgente' => 'Urgente',
                    ]),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label('Completar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn ($record) => $record->update(['is_completed' => true, 'completed_at' => now()]))
                    ->visible(fn ($record) => ! $record->is_completed)
                    ->requiresConfirmation(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
