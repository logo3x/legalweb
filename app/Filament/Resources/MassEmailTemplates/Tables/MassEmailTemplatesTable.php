<?php

namespace App\Filament\Resources\MassEmailTemplates\Tables;

use App\Models\MassEmailTemplate;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MassEmailTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => MassEmailTemplate::CATEGORIES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'onboarding' => 'success',
                        'retencion' => 'warning',
                        'reactivacion' => 'info',
                        'encuesta' => 'primary',
                        'novedades' => 'gray',
                        'marketing' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('subject')
                    ->label('Asunto')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->subject),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Actualizada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('category')
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->options(MassEmailTemplate::CATEGORIES),
                TernaryFilter::make('is_active')
                    ->label('Activa')
                    ->placeholder('Todas')
                    ->trueLabel('Solo activas')
                    ->falseLabel('Solo inactivas'),
            ])
            ->recordActions([
                Action::make('duplicate')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function ($record) {
                        $copy = $record->replicate();
                        $copy->name = $record->name.' (copia)';
                        $copy->is_active = false;
                        $copy->save();
                    })
                    ->successNotificationTitle('Plantilla duplicada como inactiva. Editela antes de activarla.'),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
