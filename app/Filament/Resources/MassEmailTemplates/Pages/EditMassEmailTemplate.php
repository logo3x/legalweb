<?php

namespace App\Filament\Resources\MassEmailTemplates\Pages;

use App\Filament\Resources\MassEmailTemplates\MassEmailTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMassEmailTemplate extends EditRecord
{
    protected static string $resource = MassEmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
