<?php

namespace App\Filament\Resources\MassEmailTemplates;

use App\Filament\Resources\MassEmailTemplates\Pages\CreateMassEmailTemplate;
use App\Filament\Resources\MassEmailTemplates\Pages\EditMassEmailTemplate;
use App\Filament\Resources\MassEmailTemplates\Pages\ListMassEmailTemplates;
use App\Filament\Resources\MassEmailTemplates\Schemas\MassEmailTemplateForm;
use App\Filament\Resources\MassEmailTemplates\Tables\MassEmailTemplatesTable;
use App\Models\MassEmailTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MassEmailTemplateResource extends Resource
{
    protected static ?string $model = MassEmailTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static string|UnitEnum|null $navigationGroup = 'Super Admin';

    protected static ?string $modelLabel = 'Plantilla';

    protected static ?string $pluralModelLabel = 'Plantillas de correo';

    protected static ?string $navigationLabel = 'Plantillas de correo';

    protected static ?int $navigationSort = 50;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'superadmin';
    }

    public static function form(Schema $schema): Schema
    {
        return MassEmailTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MassEmailTemplatesTable::configure($table);
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
            'index' => ListMassEmailTemplates::route('/'),
            'create' => CreateMassEmailTemplate::route('/create'),
            'edit' => EditMassEmailTemplate::route('/{record}/edit'),
        ];
    }
}
