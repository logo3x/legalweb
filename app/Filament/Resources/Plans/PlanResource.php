<?php

namespace App\Filament\Resources\Plans;

use App\Filament\Resources\Plans\Pages\ManagePlans;
use App\Models\Plan;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Super Admin';

    protected static ?string $modelLabel = 'Plan';

    protected static ?string $pluralModelLabel = 'Planes';

    protected static ?int $navigationSort = 31;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'superadmin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->label('Descripcion')
                    ->columnSpanFull(),
                TextInput::make('price_monthly')
                    ->label('Precio Mensual (COP)')
                    ->numeric()
                    ->required()
                    ->default(0),
                TextInput::make('price_yearly')
                    ->label('Precio Semestral (COP)')
                    ->numeric()
                    ->required()
                    ->default(0),
                TextInput::make('max_cases')
                    ->label('Max Casos')
                    ->numeric()
                    ->required()
                    ->default(0),
                TextInput::make('max_users')
                    ->label('Max Usuarios')
                    ->numeric()
                    ->required()
                    ->default(1),
                TextInput::make('max_storage_mb')
                    ->label('Max Almacenamiento (MB)')
                    ->numeric()
                    ->required()
                    ->default(100),
                Toggle::make('has_portal')
                    ->label('Portal del Cliente'),
                Toggle::make('has_notifications')
                    ->label('Notificaciones'),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
                TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable(),
                TextColumn::make('price_monthly')
                    ->label('Precio/mes')
                    ->formatStateUsing(fn (int $state): string => '$'.number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('price_yearly')
                    ->label('Precio/semestre')
                    ->formatStateUsing(fn (int $state): string => '$'.number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('max_cases')
                    ->label('Casos')
                    ->sortable(),
                TextColumn::make('max_users')
                    ->label('Usuarios')
                    ->sortable(),
                IconColumn::make('has_portal')
                    ->label('Portal')
                    ->boolean(),
                IconColumn::make('has_notifications')
                    ->label('Notif.')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('subscriptions_count')
                    ->label('Suscripciones')
                    ->counts('subscriptions')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePlans::route('/'),
        ];
    }
}
