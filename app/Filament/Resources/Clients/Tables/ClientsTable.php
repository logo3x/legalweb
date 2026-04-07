<?php

namespace App\Filament\Resources\Clients\Tables;

use App\Models\LegalCase;
use App\Services\TybaService;
use Filament\Actions\Action;
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
                    ->description(fn ($record) => $record->is_demo ? 'Ejemplo' : null)
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
                Action::make('buscar_procesos')
                    ->label('Buscar Procesos')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->modalWidth('3xl')
                    ->modalHeading(fn ($record) => "Procesos de {$record->first_name} {$record->last_name}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(function ($record) {
                        $name = trim("{$record->last_name} {$record->first_name}");
                        $tyba = app(TybaService::class);
                        $procesos = $tyba->searchByName($name);

                        // Marcar cuales ya estan importados
                        $existingRadicados = LegalCase::withoutGlobalScopes()
                            ->where('firm_id', auth()->user()->firm_id)
                            ->whereNotNull('external_case_number')
                            ->pluck('external_case_number')
                            ->toArray();

                        return view('filament.modals.client-processes', [
                            'procesos' => $procesos,
                            'existingRadicados' => $existingRadicados,
                            'clientName' => "{$record->first_name} {$record->last_name}",
                            'totalFound' => count($procesos),
                        ]);
                    }),
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
