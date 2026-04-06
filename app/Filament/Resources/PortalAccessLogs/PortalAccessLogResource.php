<?php

namespace App\Filament\Resources\PortalAccessLogs;

use App\Models\PortalAccessLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PortalAccessLogResource extends Resource
{
    protected static ?string $model = PortalAccessLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEye;

    protected static string|UnitEnum|null $navigationGroup = 'Super Admin';

    protected static ?string $modelLabel = 'Acceso Portal';

    protected static ?string $pluralModelLabel = 'Accesos al Portal';

    protected static ?int $navigationSort = 34;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'superadmin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('firm.name')
                    ->label('Firma')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('legalCase.case_number')
                    ->label('Caso')
                    ->searchable(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable(),
                TextColumn::make('action')
                    ->label('Accion')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'accept_terms' => 'success',
                        'view' => 'info',
                        'landing' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'accept_terms' => 'Acepto terminos',
                        'view' => 'Consulto caso',
                        'landing' => 'Visito portal',
                        default => $state,
                    }),
                TextColumn::make('user_agent')
                    ->label('Dispositivo')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('action')
                    ->label('Accion')
                    ->options([
                        'landing' => 'Visito portal',
                        'accept_terms' => 'Acepto terminos',
                        'view' => 'Consulto caso',
                    ]),
                SelectFilter::make('firm_id')
                    ->label('Firma')
                    ->relationship('firm', 'name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPortalAccessLogs::route('/'),
        ];
    }
}
