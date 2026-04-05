<?php

namespace App\Filament\Widgets;

use App\Models\Reminder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingReminders extends TableWidget
{
    protected static ?string $heading = 'Proximos Eventos';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Reminder::query()
                    ->where('user_id', auth()->id())
                    ->where('is_completed', false)
                    ->where('due_date', '>=', now())
                    ->orderBy('due_date')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('due_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color(fn ($record) => $record->due_date->diffInDays(now()) <= 1 ? 'danger' : ($record->due_date->diffInDays(now()) <= 3 ? 'warning' : null)),
                TextColumn::make('title')
                    ->label('Titulo')
                    ->limit(40),
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
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'urgente' => 'danger',
                        'alta' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('legalCase.case_number')
                    ->label('Caso')
                    ->placeholder('-'),
            ])
            ->paginated(false);
    }
}
