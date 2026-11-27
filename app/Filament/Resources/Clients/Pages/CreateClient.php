<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    /**
     * Desde Buscar procesos llega el documento (y el nombre) de la persona consultada.
     */
    protected function afterFill(): void
    {
        $prefill = collect(request()->only(['document_type', 'document_number', 'first_name', 'last_name']))
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => mb_substr(trim($value), 0, 100));

        if ($prefill->has('document_type') && ! in_array($prefill['document_type'], ['CC', 'CE', 'NIT', 'PP', 'TI'], true)) {
            $prefill->forget('document_type');
        }

        if ($prefill->isNotEmpty()) {
            $this->data = array_merge($this->data ?? [], $prefill->all());
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['firm_id'] = auth()->user()->firm_id;

        // Sin abogado asignado, queda a cargo de quien lo crea (asi un colaborador sigue viendolo).
        $data['user_id'] ??= auth()->id();

        return $data;
    }
}
