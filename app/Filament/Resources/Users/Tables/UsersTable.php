<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&background=3A86FF&color=fff&size=40'),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Rol')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'superadmin' => 'danger',
                        'admin' => 'warning',
                        'abogado' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('firm.name')
                    ->label('Firma')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin firma'),
                TextColumn::make('firm.activeSubscription.plan.name')
                    ->label('Plan')
                    ->badge()
                    ->placeholder('Sin plan'),
                TextColumn::make('google_id')
                    ->label('Google')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Si' : 'No')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Registro')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->label('Rol')
                    ->options([
                        'superadmin' => 'Super Admin',
                        'admin' => 'Administrador',
                        'abogado' => 'Abogado',
                        'asistente' => 'Asistente',
                    ]),
                SelectFilter::make('firm_id')
                    ->label('Firma')
                    ->relationship('firm', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
