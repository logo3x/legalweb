<?php

namespace App\Filament\Resources\LegalCases\Pages;

use App\Filament\Resources\LegalCases\LegalCaseResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLegalCase extends ViewRecord
{
    protected static string $resource = LegalCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('compartir')
                ->label('Compartir con Cliente')
                ->icon('heroicon-o-share')
                ->color('info')
                ->action(function () {
                    $record = $this->record;

                    if (! $record->portal_token) {
                        $record->generatePortalToken();
                    }

                    $url = route('portal.show', $record->portal_token);

                    Notification::make()
                        ->title('Enlace del Portal')
                        ->body("Copie este enlace y compartalo con su cliente:\n{$url}")
                        ->success()
                        ->persistent()
                        ->send();
                }),
            Action::make('toggle_portal')
                ->label(fn () => $this->record->portal_enabled ? 'Desactivar Portal' : 'Activar Portal')
                ->icon(fn () => $this->record->portal_enabled ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                ->color(fn () => $this->record->portal_enabled ? 'danger' : 'success')
                ->action(function () {
                    $record = $this->record;

                    if (! $record->portal_token) {
                        $record->generatePortalToken();
                    } else {
                        $record->update(['portal_enabled' => ! $record->portal_enabled]);
                    }

                    $status = $record->fresh()->portal_enabled ? 'activado' : 'desactivado';

                    Notification::make()
                        ->title("Portal {$status}")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->record->portal_enabled ? 'Desactivar portal del cliente' : 'Activar portal del cliente')
                ->modalDescription(fn () => $this->record->portal_enabled
                    ? 'El cliente ya no podra ver el estado de su caso.'
                    : 'El cliente podra ver el estado de su caso a traves del enlace.'),
            EditAction::make()
                ->label('Editar Caso'),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
