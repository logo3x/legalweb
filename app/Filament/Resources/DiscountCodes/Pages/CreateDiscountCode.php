<?php

namespace App\Filament\Resources\DiscountCodes\Pages;

use App\Filament\Resources\DiscountCodes\DiscountCodeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDiscountCode extends CreateRecord
{
    protected static string $resource = DiscountCodeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['current_uses'] = 0;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
