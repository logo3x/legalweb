<?php

namespace App\Filament\Resources\Documents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('legalCase.case_number')
                    ->label('Caso')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Documento')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('responsible')
                    ->label('Responsable')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'cliente' => 'info',
                        'abogado' => 'success',
                        'firma' => 'primary',
                        'contraparte' => 'danger',
                        'juzgado' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : '-'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'pendiente' => 'gray',
                        'solicitado' => 'info',
                        'en_tramite' => 'warning',
                        'recibido' => 'success',
                        'no_aplica' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pendiente' => 'Pendiente',
                        'solicitado' => 'Solicitado',
                        'en_tramite' => 'En tramite',
                        'recibido' => 'Recibido',
                        'no_aplica' => 'No aplica',
                        default => '-',
                    }),
                TextColumn::make('entity')
                    ->label('Entidad')
                    ->placeholder('-')
                    ->limit(25)
                    ->toggleable(),
                TextColumn::make('estimated_cost')
                    ->label('Valor')
                    ->money('COP', locale: 'es_CO')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
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
