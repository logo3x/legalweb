<?php

namespace App\Filament\Resources\MassEmailTemplates\Pages;

use App\Filament\Resources\MassEmailTemplates\MassEmailTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMassEmailTemplates extends ListRecords
{
    protected static string $resource = MassEmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
