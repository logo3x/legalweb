<?php

namespace App\Filament\Resources\CaseEvents\Pages;

use App\Filament\Resources\CaseEvents\CaseEventResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCaseEvent extends EditRecord
{
    protected static string $resource = CaseEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
