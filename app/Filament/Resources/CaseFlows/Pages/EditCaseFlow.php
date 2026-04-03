<?php

namespace App\Filament\Resources\CaseFlows\Pages;

use App\Filament\Resources\CaseFlows\CaseFlowResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCaseFlow extends EditRecord
{
    protected static string $resource = CaseFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
