<?php

namespace App\Filament\Resources\PortalAccessLogs\Pages;

use App\Filament\Resources\PortalAccessLogs\PortalAccessLogResource;
use Filament\Resources\Pages\ListRecords;

class ListPortalAccessLogs extends ListRecords
{
    protected static string $resource = PortalAccessLogResource::class;
}
