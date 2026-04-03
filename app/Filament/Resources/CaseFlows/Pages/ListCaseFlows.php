<?php

namespace App\Filament\Resources\CaseFlows\Pages;

use App\Filament\Resources\CaseFlows\CaseFlowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCaseFlows extends ListRecords
{
    protected static string $resource = CaseFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
