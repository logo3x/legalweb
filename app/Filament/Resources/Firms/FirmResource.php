<?php

namespace App\Filament\Resources\Firms;

use App\Filament\Resources\Firms\Pages\CreateFirm;
use App\Filament\Resources\Firms\Pages\EditFirm;
use App\Filament\Resources\Firms\Pages\ListFirms;
use App\Filament\Resources\Firms\Schemas\FirmForm;
use App\Filament\Resources\Firms\Tables\FirmsTable;
use App\Models\Firm;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FirmResource extends Resource
{
    protected static ?string $model = Firm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string|UnitEnum|null $navigationGroup = 'Super Admin';

    protected static ?string $modelLabel = 'Firma';

    protected static ?string $pluralModelLabel = 'Firmas';

    protected static ?int $navigationSort = 30;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'superadmin';
    }

    public static function form(Schema $schema): Schema
    {
        return FirmForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FirmsTable::configure($table);
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
            'index' => ListFirms::route('/'),
            'create' => CreateFirm::route('/create'),
            'edit' => EditFirm::route('/{record}/edit'),
        ];
    }
}
