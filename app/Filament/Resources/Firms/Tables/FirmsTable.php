<?php

namespace App\Filament\Resources\Firms\Tables;

use App\Models\Firm;
use App\Models\Reminder;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FirmsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl('/images/default-firm-logo.svg'),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->email ?? null),
                TextColumn::make('tracking_status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => Firm::TRACKING_STATUSES[$state] ?? 'Activo')
                    ->color(fn (?string $state): string => match ($state) {
                        'activo' => 'success',
                        'prospecto' => 'info',
                        'pausado' => 'warning',
                        'perdido' => 'danger',
                        'no_contactar' => 'gray',
                        default => 'success',
                    }),
                TextColumn::make('tracking_tags')
                    ->label('Etiquetas')
                    ->badge()
                    ->separator(',')
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('activeSubscription.plan.name')
                    ->label('Plan')
                    ->badge()
                    ->default('Sin plan'),
                TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->counts('users')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('legal_cases_count')
                    ->label('Casos')
                    ->counts('legalCases')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('last_login_at')
                    ->label('Ultimo acceso')
                    ->getStateUsing(fn ($record) => $record->users()->max('last_login_at'))
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->diffForHumans() : 'Nunca')
                    ->tooltip(fn ($state) => $state ? Carbon::parse($state)->format('d/m/Y H:i') : 'Sin registros de login')
                    ->color(function ($state) {
                        if (! $state) {
                            return 'danger';
                        }
                        $days = Carbon::parse($state)->diffInDays(now());

                        return match (true) {
                            $days <= 7 => 'success',
                            $days <= 30 => 'warning',
                            default => 'danger',
                        };
                    }),
                TextColumn::make('created_at')
                    ->label('Registro')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at?->diffForHumans()),
                TextColumn::make('city')
                    ->label('Ciudad')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('clients_count')
                    ->label('Clientes')
                    ->counts('clients')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('tracking_status')
                    ->label('Estado seguimiento')
                    ->options(Firm::TRACKING_STATUSES),
                Filter::make('without_login')
                    ->label('Sin login en 30 dias')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDoesntHave('users', fn ($q) => $q->where('last_login_at', '>=', now()->subDays(30)))),
                Filter::make('never_logged_in')
                    ->label('Nunca iniciaron sesion')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDoesntHave('users', fn ($q) => $q->whereNotNull('last_login_at'))),
                Filter::make('registered_at')
                    ->label('Fecha de registro')
                    ->schema([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['hasta'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->filtersFormColumns(2)
            ->persistFiltersInSession()
            ->recordActions([
                Action::make('agregar_recordatorio')
                    ->label('Agendar')
                    ->icon('heroicon-o-bell-alert')
                    ->color('warning')
                    ->modalHeading(fn ($record) => "Crear recordatorio para {$record->name}")
                    ->modalSubmitActionLabel('Crear recordatorio')
                    ->modalCancelActionLabel('Cancelar')
                    ->form([
                        TextInput::make('title')
                            ->label('Titulo')
                            ->required()
                            ->placeholder('Ej: Llamar para verificar pago, demo en vivo, seguimiento'),
                        Textarea::make('description')
                            ->label('Detalles')
                            ->rows(3),
                        Select::make('priority')
                            ->label('Prioridad')
                            ->options([
                                'baja' => 'Baja',
                                'media' => 'Media',
                                'alta' => 'Alta',
                                'urgente' => 'Urgente',
                            ])
                            ->default('media')
                            ->required(),
                        DateTimePicker::make('due_date')
                            ->label('Fecha limite')
                            ->required()
                            ->default(now()->addDays(3)->setHour(9)->setMinute(0)),
                        DateTimePicker::make('remind_at')
                            ->label('Recordar el')
                            ->default(now()->addDays(2)->setHour(9)->setMinute(0)),
                    ])
                    ->action(function (array $data, $record) {
                        Reminder::create([
                            'firm_id' => $record->id,
                            'user_id' => auth()->id(),
                            'title' => '['.$record->name.'] '.$data['title'],
                            'description' => $data['description'] ?? null,
                            'type' => 'tarea',
                            'priority' => $data['priority'],
                            'due_date' => $data['due_date'],
                            'remind_at' => $data['remind_at'] ?? null,
                        ]);

                        $record->update(['last_admin_review_at' => now()]);

                        Notification::make()
                            ->title('Recordatorio creado')
                            ->body('Lo encontrara en su agenda personal con el prefijo ['.$record->name.'].')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
