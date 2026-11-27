<?php

namespace App\Filament\Resources\Clients\Tables;

use App\Models\Client;
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
            ->stackedOnMobile()
            ->modifyQueryUsing(fn ($query) => $query->withCount(['legalCases as active_cases_count' => fn ($q) => $q->whereIn('status', ['abierto', 'en_progreso', 'en_espera'])]))
            ->columns([
                // Nombre completo con el documento debajo: una sola columna en lugar de cuatro.
                TextColumn::make('first_name')
                    ->label('Cliente')
                    ->formatStateUsing(fn (Client $record): string => trim("{$record->first_name} {$record->last_name}"))
                    ->searchable(['first_name', 'last_name', 'document_number'])
                    ->sortable(['first_name', 'last_name'])
                    ->weight('medium')
                    ->description(fn (Client $record): string => trim("{$record->document_type} {$record->document_number}").($record->is_demo ? ' · Ejemplo' : '')),
                // Sin correo no se le pueden enviar avisos: se marca para que el abogado lo complete.
                TextColumn::make('email')
                    ->label('Contacto')
                    ->searchable(['email', 'phone'])
                    ->description(fn (Client $record): ?string => $record->phone ?: null)
                    ->placeholder('Sin correo')
                    ->color(fn (Client $record): ?string => blank($record->email) ? 'warning' : null)
                    ->icon(fn (Client $record): ?string => blank($record->email) ? 'heroicon-m-exclamation-circle' : null)
                    ->tooltip(fn (Client $record): ?string => blank($record->email) ? 'Agregue el correo para poder avisarle de sus actuaciones.' : null),
                TextColumn::make('active_cases_count')
                    ->label('Casos activos')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('process_monitoring_enabled')
                    ->label('Vigilancia')
                    ->state(fn (Client $record): ?string => $record->process_monitoring_enabled ? 'Activa' : null)
                    ->icon(fn (?string $state): ?string => $state ? 'heroicon-m-shield-check' : null)
                    ->color('success')
                    ->placeholder('—')
                    ->tooltip(fn (Client $record): ?string => $record->process_monitoring_enabled
                        ? ($record->process_monitoring_last_at ? 'Última revisión: '.$record->process_monitoring_last_at->format('d/m/Y H:i') : 'Aún sin revisar')
                        : 'Cada noche buscamos su documento en la Rama Judicial y le avisamos si aparece en procesos nuevos.'),
                TextColumn::make('city')
                    ->label('Ciudad')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // En firmas con un solo abogado la columna siempre diria lo mismo.
                TextColumn::make('user.name')
                    ->label('Abogado')
                    ->sortable()
                    ->visible(fn (): bool => (auth()->user()?->firm?->users()->count() ?? 0) > 1)
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('buscar_procesos')
                    ->label('Buscar procesos')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('gray')
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
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar'),
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
