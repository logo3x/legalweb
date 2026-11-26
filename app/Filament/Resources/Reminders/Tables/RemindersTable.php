<?php

namespace App\Filament\Resources\Reminders\Tables;

use App\Filament\Resources\Reminders\ReminderResource;
use App\Models\Reminder;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RemindersTable
{
    private const TYPE_LABELS = [
        'audiencia' => 'Audiencia',
        'vencimiento' => 'Vencimiento',
        'reunion' => 'Reunión',
        'tarea' => 'Tarea',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->columns([
                // La fecha va primero: es lo que se busca al abrir la agenda.
                TextColumn::make('due_date')
                    ->label('Cuándo')
                    ->dateTime('d/m/Y g:i a')
                    ->sortable()
                    ->description(fn (Reminder $record): ?string => self::relativeWhen($record))
                    ->color(fn (Reminder $record): ?string => self::isOverdue($record) ? 'danger' : null)
                    ->weight(fn (Reminder $record): ?string => self::isOverdue($record) ? 'medium' : null),
                TextColumn::make('title')
                    ->label('Qué')
                    ->searchable()
                    ->sortable()
                    ->limit(70)
                    ->wrap()
                    ->weight('medium')
                    ->color(fn (Reminder $record): ?string => $record->is_completed ? 'gray' : null)
                    ->description(fn (Reminder $record): ?string => $record->legalCase
                        ? $record->legalCase->case_number.' · '.str($record->legalCase->title)->limit(50)
                        : null),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => self::TYPE_LABELS[$state] ?? 'Recordatorio'),
                // Solo se marca lo que pide atencion; baja y media no compiten por la mirada.
                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->state(fn (Reminder $record): ?string => in_array($record->priority, ['alta', 'urgente'], true) ? $record->priority : null)
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => $state === 'urgente' ? 'danger' : 'warning')
                    ->placeholder('')
                    ->visibleFrom('md'),
            ])
            ->defaultSort('due_date', 'asc')
            ->filters([
                TernaryFilter::make('is_completed')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Completados')
                    ->falseLabel('Pendientes')
                    ->default(false),
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'audiencia' => 'Audiencia',
                        'vencimiento' => 'Vencimiento',
                        'reunion' => 'Reunión',
                        'tarea' => 'Tarea',
                        'recordatorio' => 'Recordatorio',
                    ]),
                SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'baja' => 'Baja',
                        'media' => 'Media',
                        'alta' => 'Alta',
                        'urgente' => 'Urgente',
                    ]),
                SelectFilter::make('legal_case_id')
                    ->label('Caso')
                    ->relationship('legalCase', 'case_number', fn ($query) => $query->where('firm_id', auth()->user()->firm_id))
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->case_number} - ".str($record->title)->limit(40))
                    ->searchable()
                    ->preload(),
                Filter::make('vencidos')
                    ->label('Solo vencidos')
                    ->toggle()
                    ->query(fn ($query) => $query->where('is_completed', false)
                        ->whereNotNull('due_date')
                        ->where('due_date', '<', now())),
                Filter::make('proximos_7')
                    ->label('Proximos 7 dias')
                    ->toggle()
                    ->query(fn ($query) => $query->where('is_completed', false)
                        ->whereNotNull('due_date')
                        ->whereBetween('due_date', [now(), now()->addDays(7)])),
                Filter::make('due_date')
                    ->label('Rango de fechas')
                    ->schema([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'] ?? null, fn ($q, $d) => $q->whereDate('due_date', '>=', $d))
                            ->when($data['hasta'] ?? null, fn ($q, $d) => $q->whereDate('due_date', '<=', $d));
                    })
                    ->indicateUsing(function (array $data): array {
                        $i = [];
                        if ($data['desde'] ?? null) {
                            $i[] = 'Desde: '.Carbon::parse($data['desde'])->format('d/m/Y');
                        }
                        if ($data['hasta'] ?? null) {
                            $i[] = 'Hasta: '.Carbon::parse($data['hasta'])->format('d/m/Y');
                        }

                        return $i;
                    }),
            ])
            ->filtersFormColumns(2)
            ->persistFiltersInSession()
            ->recordActions([
                Action::make('complete')
                    ->label('Completar')
                    ->icon('heroicon-o-check')
                    ->color('gray')
                    ->action(fn ($record) => $record->update(['is_completed' => true, 'completed_at' => now()]))
                    ->visible(fn ($record) => ! $record->is_completed)
                    ->authorize(fn ($record): bool => ReminderResource::canEdit($record))
                    ->requiresConfirmation(),
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function isOverdue(Reminder $record): bool
    {
        return ! $record->is_completed && $record->due_date?->isPast();
    }

    /**
     * «Hoy», «Mañana», «En 4 días» o «Vencido hace 2 días»: la fecha exacta queda arriba.
     */
    private static function relativeWhen(Reminder $record): ?string
    {
        if ($record->is_completed) {
            return 'Completado';
        }

        $due = $record->due_date;

        if (! $due) {
            return null;
        }

        $days = (int) now()->startOfDay()->diffInDays($due->copy()->startOfDay(), false);

        return match (true) {
            $due->isPast() && $days === 0 => 'Hoy · ya pasó la hora',
            $days < 0 => 'Vencido hace '.abs($days).' '.(abs($days) === 1 ? 'día' : 'días'),
            $days === 0 => 'Hoy',
            $days === 1 => 'Mañana',
            default => "En {$days} días",
        };
    }
}
