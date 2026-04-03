<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        if ($this->record->is_demo) {
            Notification::make()
                ->title('Cliente de ejemplo')
                ->body('Los clientes de ejemplo no se pueden editar. Cree un nuevo cliente para empezar.')
                ->warning()
                ->send();

            $this->halt();
        }
    }

    protected function getSaveFormAction(): Action
    {
        $action = parent::getSaveFormAction();

        if ($this->record->is_demo) {
            $action->label('Cliente de ejemplo (no editable)')->disabled();
        }

        return $action;
    }
}
