<?php

namespace App\Filament\Resources\Clients\Tables;

use App\Models\LegalCase;
use App\Services\TybaService;
use App\Services\UsageLimiter;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

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
                IconColumn::make('process_monitoring_enabled')
                    ->label('Vigilancia')
                    ->boolean()
                    ->tooltip(fn ($record): string => $record->process_monitoring_last_at
                        ? 'Ultima revision: '.$record->process_monitoring_last_at->format('d/m/Y H:i')
                        : 'Aun sin revisar')
                    ->toggleable(),
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
                        if (! UsageLimiter::attempt('judicial')) {
                            return new HtmlString('<p class="text-sm text-gray-500">Espere un momento antes de volver a consultar la Rama Judicial.</p>');
                        }

                        $name = trim("{$record->last_name} {$record->first_name}");
                        $tyba = app(TybaService::class);
                        $procesos = $tyba->searchByName($name);

                        // Marcar cuales ya estan importados
                        $existingRadicados = LegalCase::withoutGlobalScope(SoftDeletingScope::class)
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
                    BulkAction::make('enable_monitoring')
                        ->label('Activar vigilancia de procesos')
                        ->icon('heroicon-o-shield-check')
                        ->requiresConfirmation()
                        ->modalDescription('Cada noche se buscara el documento de estos clientes en la Rama Judicial y se le avisara si aparecen en procesos nuevos.')
                        ->visible(fn (): bool => auth()->user()?->firm?->planLimits()->hasClientMonitoring() ?? true)
                        ->action(fn ($records) => $records->each->update(['process_monitoring_enabled' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('disable_monitoring')
                        ->label('Desactivar vigilancia')
                        ->icon('heroicon-o-shield-exclamation')
                        ->action(fn ($records) => $records->each->update(['process_monitoring_enabled' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
