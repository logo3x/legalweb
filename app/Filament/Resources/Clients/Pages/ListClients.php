<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('buscar_procesos_tyba')
                ->label('Buscar Procesos')
                ->icon('heroicon-o-magnifying-glass')
                ->color('info')
                ->url(route('filament.admin.pages.buscar-procesos-tyba'))
                ->tooltip('Consultar procesos en la Rama Judicial por nombre, sin necesidad de tener al cliente registrado'),
            CreateAction::make(),
        ];
    }
}
