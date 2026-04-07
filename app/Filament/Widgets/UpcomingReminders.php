<?php

namespace App\Filament\Widgets;

use App\Models\Reminder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingReminders extends TableWidget
{
    protected static ?string $heading = 'Alertas y Vencimientos Proximos';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Reminder::query()
                    ->where('user_id', auth()->id())
                    ->where('is_completed', false)
                    ->where('due_date', '>=', now()->subDay())
                    ->orderBy('due_date')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('due_date')
                    ->label('Vence')
                    ->formatStateUsing(function ($state) {
                        $days = (int) now()->startOfDay()->diffInDays($state->startOfDay(), false);

                        $dateStr = $state->format('d/m/Y');

                        return match (true) {
                            $days < 0 => "VENCIDO ({$dateStr})",
                            $days === 0 => "HOY ({$dateStr})",
                            $days === 1 => "MANANA ({$dateStr})",
                            $days <= 7 => "{$days} dias ({$dateStr})",
                            default => $dateStr,
                        };
                    })
                    ->color(function ($state) {
                        $days = (int) now()->startOfDay()->diffInDays($state->startOfDay(), false);

                        return match (true) {
                            $days < 0 => 'danger',
                            $days === 0 => 'danger',
                            $days <= 2 => 'warning',
                            default => null,
                        };
                    })
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Descripcion')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'audiencia' => 'warning',
                        'vencimiento' => 'danger',
                        'reunion' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('priority')
                    ->label('Urgencia')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'urgente' => 'danger',
                        'alta' => 'warning',
                        'media' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('legalCase.case_number')
                    ->label('Caso')
                    ->placeholder('-')
                    ->url(fn ($record) => $record->legal_case_id
                        ? route('filament.admin.resources.legal-cases.view', $record->legal_case_id)
                        : null),
            ])
            ->paginated(false)
            ->emptyStateHeading('Sin vencimientos proximos')
            ->emptyStateDescription('No tiene alertas pendientes. Las alertas se crean automaticamente al sincronizar con la Rama Judicial.');
    }
}
