<?php

namespace App\Filament\Resources\MassEmailCampaigns;

use App\Filament\Resources\MassEmailCampaigns\Pages\CreateMassEmailCampaign;
use App\Filament\Resources\MassEmailCampaigns\Pages\EditMassEmailCampaign;
use App\Filament\Resources\MassEmailCampaigns\Pages\ListMassEmailCampaigns;
use App\Filament\Resources\MassEmailCampaigns\Schemas\MassEmailCampaignForm;
use App\Filament\Resources\MassEmailCampaigns\Tables\MassEmailCampaignsTable;
use App\Models\MassEmailCampaign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MassEmailCampaignResource extends Resource
{
    protected static ?string $model = MassEmailCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|UnitEnum|null $navigationGroup = 'Super Admin';

    protected static ?string $modelLabel = 'Correo masivo';

    protected static ?string $pluralModelLabel = 'Correos masivos';

    protected static ?string $navigationLabel = 'Correos masivos';

    protected static ?int $navigationSort = 40;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'superadmin';
    }

    public static function form(Schema $schema): Schema
    {
        return MassEmailCampaignForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MassEmailCampaignsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMassEmailCampaigns::route('/'),
            'create' => CreateMassEmailCampaign::route('/create'),
            'edit' => EditMassEmailCampaign::route('/{record}/edit'),
        ];
    }
}
