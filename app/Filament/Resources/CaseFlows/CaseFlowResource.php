<?php

namespace App\Filament\Resources\CaseFlows;

use App\Filament\Resources\CaseFlows\Pages\CreateCaseFlow;
use App\Filament\Resources\CaseFlows\Pages\EditCaseFlow;
use App\Filament\Resources\CaseFlows\Pages\ListCaseFlows;
use App\Filament\Resources\CaseFlows\Schemas\CaseFlowForm;
use App\Filament\Resources\CaseFlows\Tables\CaseFlowsTable;
use App\Models\CaseFlow;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CaseFlowResource extends Resource
{
    protected static ?string $model = CaseFlow::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $modelLabel = 'Flujo de Proceso';

    protected static ?string $pluralModelLabel = 'Flujos de Proceso';

    protected static ?int $navigationSort = 11;

    /**
     * Los tipos y flujos procesales son compartidos por todas las firmas: solo el superadmin los modifica.
     */
    private static function isSuperadmin(): bool
    {
        return auth()->user()?->role === 'superadmin';
    }

    public static function canCreate(): bool
    {
        return static::isSuperadmin();
    }

    public static function canEdit(Model $record): bool
    {
        return static::isSuperadmin();
    }

    public static function canDelete(Model $record): bool
    {
        return static::isSuperadmin();
    }

    public static function canDeleteAny(): bool
    {
        return static::isSuperadmin();
    }

    public static function form(Schema $schema): Schema
    {
        return CaseFlowForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CaseFlowsTable::configure($table);
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
            'index' => ListCaseFlows::route('/'),
            'create' => CreateCaseFlow::route('/create'),
            'edit' => EditCaseFlow::route('/{record}/edit'),
        ];
    }
}
