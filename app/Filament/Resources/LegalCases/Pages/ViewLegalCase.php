<?php

namespace App\Filament\Resources\LegalCases\Pages;

use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Services\AIService;
use App\Services\DocumentGenerator;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLegalCase extends ViewRecord
{
    protected static string $resource = LegalCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('ai_summary')
                    ->label('Resumen del Caso')
                    ->icon('heroicon-o-document-text')
                    ->modalWidth('2xl')
                    ->modalHeading('Resumen del Caso')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitActionLabel('Copiar al portapapeles')
                    ->form(function () {
                        $ai = app(AIService::class);
                        $result = $ai->summarizeCase($this->record);
                        $provider = $ai->getLastProvider() ?? 'N/A';

                        return [
                            Textarea::make('ai_result')
                                ->label('')
                                ->default($result ?? 'No se pudo generar el resumen. Verifique la configuracion de la IA.')
                                ->rows(15)
                                ->readOnly(),
                            Placeholder::make('provider')
                                ->label('')
                                ->content("Generado con {$provider} | ".now()->format('d/m/Y H:i')),
                        ];
                    })
                    ->action(function (array $data) {
                        $this->js("navigator.clipboard.writeText('".addslashes(str_replace(["\r", "\n"], ['\r', '\n'], $data['ai_result']))."')");
                        Notification::make()->title('Texto copiado al portapapeles')->success()->send();
                    }),
                Action::make('ai_next_step')
                    ->label('Sugerir Siguiente Paso')
                    ->icon('heroicon-o-light-bulb')
                    ->modalWidth('2xl')
                    ->modalHeading('Siguiente Paso Sugerido')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitActionLabel('Copiar al portapapeles')
                    ->form(function () {
                        $ai = app(AIService::class);
                        $result = $ai->suggestNextStep($this->record);
                        $provider = $ai->getLastProvider() ?? 'N/A';

                        return [
                            Textarea::make('ai_result')
                                ->label('')
                                ->default($result ?? 'No se pudo generar la sugerencia.')
                                ->rows(10)
                                ->readOnly(),
                            Placeholder::make('provider')
                                ->label('')
                                ->content("Generado con {$provider} | ".now()->format('d/m/Y H:i')),
                        ];
                    })
                    ->action(function (array $data) {
                        $this->js("navigator.clipboard.writeText('".addslashes(str_replace(["\r", "\n"], ['\r', '\n'], $data['ai_result']))."')");
                        Notification::make()->title('Texto copiado al portapapeles')->success()->send();
                    }),
                Action::make('ai_draft')
                    ->label('Generar Borrador Word')
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        Select::make('document_type')
                            ->label('Tipo de Documento')
                            ->options([
                                'Demanda' => 'Demanda',
                                'Contestacion de demanda' => 'Contestacion de demanda',
                                'Memorial' => 'Memorial',
                                'Recurso de apelacion' => 'Recurso de apelacion',
                                'Recurso de reposicion' => 'Recurso de reposicion',
                                'Poder' => 'Poder',
                                'Derecho de peticion' => 'Derecho de peticion',
                                'Tutela' => 'Accion de tutela',
                                'Alegatos de conclusion' => 'Alegatos de conclusion',
                                'Incidente' => 'Incidente',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $content = app(AIService::class)->draftDocument($this->record, $data['document_type']);

                        if (! $content) {
                            Notification::make()->title('Error')->body('No se pudo generar el borrador.')->danger()->send();

                            return;
                        }

                        $filePath = app(DocumentGenerator::class)->generateWord(
                            $this->record,
                            $data['document_type'],
                            $content
                        );

                        $fileName = basename($filePath);

                        $this->js("window.open('".route('download.file', $fileName)."', '_blank')");

                        Notification::make()->title('Borrador generado')->body('La descarga iniciara automaticamente.')->success()->send();
                    }),
            ])
                ->label('Asistente IA')
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->button(),
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

                    Notification::make()->title("Portal {$status}")->success()->send();
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
