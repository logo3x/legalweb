<?php

namespace App\Filament\Resources\CaseEvents\Pages;

use App\Filament\Resources\CaseEvents\CaseEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCaseEvents extends ListRecords
{
    protected static string $resource = CaseEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
