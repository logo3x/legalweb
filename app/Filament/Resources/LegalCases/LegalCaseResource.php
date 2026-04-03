<?php

namespace App\Filament\Resources\LegalCases;

use App\Filament\Resources\LegalCases\Pages\CreateLegalCase;
use App\Filament\Resources\LegalCases\Pages\EditLegalCase;
use App\Filament\Resources\LegalCases\Pages\ListLegalCases;
use App\Filament\Resources\LegalCases\Schemas\LegalCaseForm;
use App\Filament\Resources\LegalCases\Tables\LegalCasesTable;
use App\Models\LegalCase;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LegalCaseResource extends Resource
{
    protected static ?string $model = LegalCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $modelLabel = 'Caso';

    protected static ?string $pluralModelLabel = 'Casos';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return LegalCaseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LegalCasesTable::configure($table);
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
            'index' => ListLegalCases::route('/'),
            'create' => CreateLegalCase::route('/create'),
            'edit' => EditLegalCase::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
