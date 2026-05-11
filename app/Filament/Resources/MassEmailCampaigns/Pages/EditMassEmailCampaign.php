<?php

namespace App\Filament\Resources\MassEmailCampaigns\Pages;

use App\Filament\Resources\MassEmailCampaigns\MassEmailCampaignResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMassEmailCampaign extends EditRecord
{
    protected static string $resource = MassEmailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
