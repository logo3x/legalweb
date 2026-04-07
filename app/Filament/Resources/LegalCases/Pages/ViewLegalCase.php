<?php

namespace App\Filament\Resources\LegalCases\Pages;

use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\CaseEvent;
use App\Services\AIService;
use App\Services\DocumentGenerator;
use App\Services\TybaService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

                        $this->js("window.location.href = '".route('download.file', $fileName)."'");

                        Notification::make()->title('Borrador generado')->body('La descarga iniciara automaticamente.')->success()->send();
                    }),
            ])
                ->label('Asistente IA')
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->button(),
            Action::make('sync_tyba')
                ->label('Sincronizar Tyba')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => (bool) $this->record->external_case_number)
                ->requiresConfirmation()
                ->modalHeading('Sincronizar con Rama Judicial')
                ->modalDescription(fn () => "Se consultara el radicado {$this->record->external_case_number} en la API de la Rama Judicial para actualizar datos del proceso y registrar nuevas actuaciones.")
                ->modalSubmitActionLabel('Sincronizar')
                ->action(function () {
                    $tyba = app(TybaService::class);
                    $info = $tyba->extractProcessInfo($this->record->external_case_number);

                    if (! $info) {
                        Notification::make()
                            ->title('No se pudo consultar')
                            ->body('Verifique que el radicado sea correcto.')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Actualizar datos del caso
                    $updates = ['last_tyba_sync' => now()];

                    if (! empty($info['despacho'])) {
                        $updates['court'] = $info['despacho'];
                    }
                    if (! empty($info['ponente'])) {
                        $updates['judge'] = mb_convert_case(mb_strtolower($info['ponente']), MB_CASE_TITLE, 'UTF-8');
                    }

                    // Actualizar contraparte si no tiene
                    if (empty($this->record->opposing_party) && ! empty($info['sujetos'])) {
                        $demandados = collect($info['sujetos'])
                            ->filter(fn ($s) => str_contains(strtolower($s['rol']), 'demandado'))
                            ->pluck('nombre')->unique()->join(', ');
                        if ($demandados) {
                            $updates['opposing_party'] = $demandados;
                        }
                    }

                    // Actualizar descripcion con sujetos actualizados
                    $description = "Importado desde Rama Judicial\n";
                    $description .= "Tipo: {$info['tipo_proceso']}\nClase: {$info['clase_proceso']}\n";
                    $description .= "Departamento: {$info['departamento']}\n";
                    $description .= "Despacho: {$info['despacho']}\n";
                    if (! empty($info['ponente'])) {
                        $description .= "Ponente: {$info['ponente']}\n";
                    }
                    if (! empty($info['sujetos'])) {
                        $description .= "\nSujetos procesales:\n";
                        foreach ($info['sujetos'] as $s) {
                            $description .= "- {$s['rol']}: {$s['nombre']}\n";
                        }
                    }
                    $updates['description'] = $description;

                    $this->record->update($updates);

                    // Registrar actuaciones nuevas
                    $newCount = 0;
                    foreach ($info['actuaciones'] as $a) {
                        $date = null;
                        foreach (['d/m/Y', 'Y-m-d'] as $fmt) {
                            try {
                                $date = Carbon::createFromFormat($fmt, trim($a['fecha']));

                                break;
                            } catch (\Exception) {
                            }
                        }

                        if (! $date) {
                            continue;
                        }

                        $title = $a['tipo'].($a['ciclo'] ? " ({$a['ciclo']})" : '');

                        $exists = CaseEvent::where('legal_case_id', $this->record->id)
                            ->where('event_date', $date)
                            ->where('title', $title)
                            ->exists();

                        if (! $exists) {
                            CaseEvent::create([
                                'legal_case_id' => $this->record->id,
                                'title' => $title,
                                'event_date' => $date,
                                'event_type' => 'actuacion',
                                'description' => ($a['anotacion'] ?: 'Sincronizado desde Rama Judicial.').' Radicado: '.$this->record->external_case_number,
                                'user_id' => auth()->id(),
                            ]);
                            $newCount++;
                        }
                    }

                    $totalActuaciones = count($info['actuaciones']);

                    if ($newCount > 0) {
                        Notification::make()
                            ->title('Sincronizacion exitosa')
                            ->body("Datos del caso actualizados. Se encontraron {$totalActuaciones} actuaciones, {$newCount} nueva(s) registradas.")
                            ->success()
                            ->persistent()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Caso actualizado')
                            ->body("Datos del proceso actualizados. Las {$totalActuaciones} actuaciones ya estaban registradas.")
                            ->info()
                            ->send();
                    }
                }),
            Action::make('compartir')
                ->label('Compartir con Cliente')
                ->icon('heroicon-o-share')
                ->color('info')
                ->modalWidth('lg')
                ->modalHeading('Compartir Portal con Cliente')
                ->modalSubmitActionLabel('Copiar enlace')
                ->modalCancelActionLabel('Cerrar')
                ->form(function () {
                    $record = $this->record;

                    if (! $record->portal_token) {
                        $record->generatePortalToken();
                        $record->refresh();
                    }

                    $url = route('portal.show', $record->portal_token);

                    return [
                        Placeholder::make('security_info')
                            ->label('')
                            ->content(
                                "Este enlace permite al cliente ver el estado de su caso. Al compartirlo tenga en cuenta:\n\n"
                                ."- El enlace es unico y exclusivo para este caso\n"
                                ."- El cliente debera aceptar los terminos de uso antes de ver la informacion\n"
                                ."- Se registra la IP y fecha de cada acceso para trazabilidad\n"
                                ."- La informacion esta protegida por el secreto profesional (Art. 74 CP)\n"
                                .'- Puede desactivar el portal en cualquier momento'
                            ),
                        TextInput::make('portal_url')
                            ->label('Enlace del portal')
                            ->default($url)
                            ->readOnly(),
                    ];
                })
                ->action(function () {
                    $url = route('portal.show', $this->record->portal_token);
                    $this->js("navigator.clipboard.writeText('".$url."')");
                    Notification::make()->title('Enlace copiado al portapapeles')->success()->send();
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
