<?php

namespace App\Filament\Resources\CaseTypes;

use App\Filament\Resources\CaseTypes\Pages\CreateCaseType;
use App\Filament\Resources\CaseTypes\Pages\EditCaseType;
use App\Filament\Resources\CaseTypes\Pages\ListCaseTypes;
use App\Filament\Resources\CaseTypes\Schemas\CaseTypeForm;
use App\Filament\Resources\CaseTypes\Tables\CaseTypesTable;
use App\Models\CaseType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CaseTypeResource extends Resource
{
    protected static ?string $model = CaseType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $modelLabel = 'Tipo de Proceso';

    protected static ?string $pluralModelLabel = 'Tipos de Proceso';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return CaseTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CaseTypesTable::configure($table);
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
            'index' => ListCaseTypes::route('/'),
            'create' => CreateCaseType::route('/create'),
            'edit' => EditCaseType::route('/{record}/edit'),
        ];
    }
}
