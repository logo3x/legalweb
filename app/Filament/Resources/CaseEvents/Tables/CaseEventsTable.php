<?php

namespace App\Filament\Resources\CaseEvents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CaseEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('legalCase.case_number')
                    ->label('Caso')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('event_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('event_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'audiencia' => 'warning',
                        'sentencia' => 'success',
                        'notificacion' => 'info',
                        default => 'gray',
                    }),
                IconColumn::make('is_milestone')
                    ->label('Hito')
                    ->boolean(),
                TextColumn::make('user.name')
                    ->label('Registrado por')
                    ->toggleable(),
            ])
            ->defaultSort('event_date', 'desc')
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Tipo')
                    ->options([
                        'actuacion' => 'Actuación',
                        'audiencia' => 'Audiencia',
                        'notificacion' => 'Notificación',
                        'memorial' => 'Memorial',
                        'auto' => 'Auto',
                        'sentencia' => 'Sentencia',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
