<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['firm_id'] = auth()->user()->firm_id;

        // Sin abogado asignado, queda a cargo de quien lo crea (asi un colaborador sigue viendolo).
        $data['user_id'] ??= auth()->id();

        return $data;
    }
}
