<?php

namespace App\Filament\Resources\Documents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
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
                SelectFilter::make('legal_case_id')
                    ->label('Caso')
                    ->relationship('legalCase', 'case_number', fn ($query) => $query->where('firm_id', auth()->user()->firm_id))
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->case_number} - ".str($record->title)->limit(40))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'solicitado' => 'Solicitado',
                        'en_tramite' => 'En tramite',
                        'recibido' => 'Recibido',
                        'no_aplica' => 'No aplica',
                    ]),
                SelectFilter::make('responsible')
                    ->label('Responsable')
                    ->options([
                        'cliente' => 'Cliente',
                        'abogado' => 'Abogado',
                        'firma' => 'Firma',
                        'contraparte' => 'Contraparte',
                        'juzgado' => 'Juzgado',
                        'otro' => 'Otro',
                    ]),
                SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'urgente' => 'Urgente',
                        'alta' => 'Alta',
                        'media' => 'Media',
                        'baja' => 'Baja',
                    ]),
                Filter::make('vencidos')
                    ->label('Solo vencidos')
                    ->toggle()
                    ->query(fn ($query) => $query->whereNotNull('due_date')
                        ->whereDate('due_date', '<', now())
                        ->whereNotIn('status', ['recibido', 'no_aplica'])),
                TrashedFilter::make(),
            ])
            ->filtersFormColumns(2)
            ->persistFiltersInSession()
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
