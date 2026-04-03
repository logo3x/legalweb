<?php

namespace App\Filament\Resources\Clients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_type')
                    ->label('Tipo Doc.')
                    ->sortable(),
                TextColumn::make('document_number')
                    ->label('Documento')
                    ->searchable(),
                TextColumn::make('first_name')
                    ->label('Nombres')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_name')
                    ->label('Apellidos')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('city')
                    ->label('Ciudad')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('Abogado')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('legal_cases_count')
                    ->label('Casos')
                    ->counts('legalCases')
                    ->sortable(),
            ])
            ->filters([
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
