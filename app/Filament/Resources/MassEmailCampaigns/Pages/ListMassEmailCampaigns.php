<?php

namespace App\Filament\Resources\MassEmailCampaigns\Pages;

use App\Filament\Resources\MassEmailCampaigns\MassEmailCampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMassEmailCampaigns extends ListRecords
{
    protected static string $resource = MassEmailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
