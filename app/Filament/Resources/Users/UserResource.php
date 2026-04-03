<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Super Admin';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static ?int $navigationSort = 33;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'superadmin';
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del Usuario')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nombre'),
                        TextEntry::make('email')
                            ->label('Correo'),
                        TextEntry::make('role')
                            ->label('Rol')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'superadmin' => 'danger',
                                'admin' => 'warning',
                                'abogado' => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('google_id')
                            ->label('Google ID')
                            ->placeholder('Sin Google')
                            ->formatStateUsing(fn (?string $state): string => $state ? 'Vinculado' : 'No vinculado')
                            ->badge()
                            ->color(fn (?string $state): string => $state ? 'success' : 'gray'),
                        TextEntry::make('created_at')
                            ->label('Registro')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('email_verified_at')
                            ->label('Email verificado')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('No verificado'),
                    ]),
                Section::make('Firma')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('firm.name')
                            ->label('Nombre de la Firma')
                            ->placeholder('Sin firma'),
                        TextEntry::make('firm.nit')
                            ->label('NIT')
                            ->placeholder('-'),
                        TextEntry::make('firm.city')
                            ->label('Ciudad')
                            ->placeholder('-'),
                        TextEntry::make('firm.email')
                            ->label('Correo Firma')
                            ->placeholder('-'),
                        TextEntry::make('firm.phone')
                            ->label('Telefono')
                            ->placeholder('-'),
                        TextEntry::make('firm.onboarding_completed')
                            ->label('Onboarding')
                            ->formatStateUsing(fn ($state): string => $state ? 'Completado' : 'Pendiente')
                            ->badge()
                            ->color(fn ($state): string => $state ? 'success' : 'warning'),
                    ]),
                Section::make('Plan y Uso')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('firm.activeSubscription.plan.name')
                            ->label('Plan Actual')
                            ->badge()
                            ->placeholder('Sin plan'),
                        TextEntry::make('firm.activeSubscription.status')
                            ->label('Estado Suscripcion')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'active' => 'success',
                                'canceled' => 'danger',
                                'expired' => 'gray',
                                default => 'warning',
                            })
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'active' => 'Activa',
                                'canceled' => 'Cancelada',
                                'expired' => 'Expirada',
                                default => $state ?? 'Sin suscripcion',
                            }),
                        TextEntry::make('firm.activeSubscription.trial_ends_at')
                            ->label('Fin de Prueba')
                            ->dateTime('d/m/Y')
                            ->placeholder('-'),
                        TextEntry::make('firm')
                            ->label('Casos Usados')
                            ->formatStateUsing(fn ($record) => $record->firm
                                ? $record->firm->realCasesCount().' / '.($record->firm->activeSubscription?->plan?->max_cases ?? 3)
                                : '-'),
                        TextEntry::make('firm.clients_count')
                            ->label('Clientes')
                            ->formatStateUsing(fn ($record) => $record->firm
                                ? $record->firm->realClientsCount()
                                : '-'),
                        TextEntry::make('firm.legalCases_count')
                            ->label('Casos Totales (inc. demo)')
                            ->formatStateUsing(fn ($record) => $record->firm
                                ? $record->firm->legalCases()->count()
                                : '-'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
