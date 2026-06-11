<?php

namespace App\Filament\Resources\DiscountCodes\Tables;

use App\Models\DiscountCode;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DiscountCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Codigo')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Codigo copiado')
                    ->fontFamily('mono')
                    ->weight('bold'),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => DiscountCode::TYPES[$state] ?? $state)
                    ->color(fn (string $state) => $state === DiscountCode::TYPE_PERCENT ? 'info' : 'success'),
                TextColumn::make('amount')
                    ->label('Valor')
                    ->formatStateUsing(fn ($state, $record) => $record->readableValue()),
                TextColumn::make('plan.name')
                    ->label('Aplica a')
                    ->placeholder('Cualquier plan'),
                TextColumn::make('current_uses')
                    ->label('Usos')
                    ->formatStateUsing(fn ($state, $record) => $record->max_uses
                        ? "{$state} / {$record->max_uses}"
                        : "{$state} / sin limite")
                    ->color(fn ($record) => $record->max_uses && $record->current_uses >= $record->max_uses ? 'danger' : null),
                TextColumn::make('valid_until')
                    ->label('Vence')
                    ->dateTime('d/m/Y')
                    ->placeholder('Sin expiracion')
                    ->color(fn ($state) => $state && Carbon::parse($state)->isPast() ? 'danger' : null),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),
            ])
            ->recordActions([
                Action::make('toggle')
                    ->label(fn ($record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->action(function ($record) {
                        $record->update(['is_active' => ! $record->is_active]);
                        Notification::make()
                            ->title($record->is_active ? 'Codigo activado' : 'Codigo desactivado')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
