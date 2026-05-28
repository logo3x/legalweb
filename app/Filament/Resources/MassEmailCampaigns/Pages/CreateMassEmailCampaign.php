<?php

namespace App\Filament\Resources\MassEmailCampaigns\Pages;

use App\Filament\Resources\MassEmailCampaigns\MassEmailCampaignResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMassEmailCampaign extends CreateRecord
{
    protected static string $resource = MassEmailCampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = ! empty($data['scheduled_at']) ? 'programado' : 'borrador';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
