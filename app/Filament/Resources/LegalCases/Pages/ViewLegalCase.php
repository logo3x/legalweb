<?php

namespace App\Filament\Resources\LegalCases\Pages;

use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\CaseEvent;
use App\Models\Reminder;
use App\Models\TybaSyncLog;
use App\Services\AIModelRegistry;
use App\Services\AIService;
use App\Services\DocumentGenerator;
use App\Services\GeneratedFileStore;
use App\Services\TybaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class ViewLegalCase extends ViewRecord
{
    protected static string $resource = LegalCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('ai_summary')
                    ->authorize(fn (): bool => $this->userCanOnCase('ai.use'))
                    ->label('Resumen del Caso')
                    ->icon('heroicon-o-document-text')
                    ->modalWidth('2xl')
                    ->modalHeading('Resumen del Caso (IA)')
                    ->modalDescription('Borrador orientativo basado en los datos del caso. NO sustituye el criterio profesional. Verifique antes de actuar.')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitActionLabel('Copiar al portapapeles')
                    ->form(fn (): array => $this->aiResultSchema('summarizeCase', 15, 'No se pudo generar el resumen. Intente con otro modelo.'))
                    ->action(function (array $data) {
                        $this->js("navigator.clipboard.writeText('".addslashes(str_replace(["\r", "\n"], ['\r', '\n'], $data['ai_result']))."')");
                        Notification::make()->title('Texto copiado al portapapeles')->success()->send();
                    }),
                Action::make('ai_next_step')
                    ->authorize(fn (): bool => $this->userCanOnCase('ai.use'))
                    ->label('Sugerir Siguiente Paso')
                    ->icon('heroicon-o-light-bulb')
                    ->modalWidth('2xl')
                    ->modalHeading('Siguiente Paso Sugerido (IA)')
                    ->modalDescription('Sugerencia basada en la etapa actual y actuaciones del caso. Solo orientativa. Verifique plazos y normativa antes de actuar.')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitActionLabel('Copiar al portapapeles')
                    ->form(fn (): array => $this->aiResultSchema('suggestNextStep', 10, 'No se pudo generar la sugerencia. Intente con otro modelo.'))
                    ->action(function (array $data) {
                        $this->js("navigator.clipboard.writeText('".addslashes(str_replace(["\r", "\n"], ['\r', '\n'], $data['ai_result']))."')");
                        Notification::make()->title('Texto copiado al portapapeles')->success()->send();
                    }),
                Action::make('ai_draft')
                    ->authorize(fn (): bool => $this->userCanOnCase('ai.use'))
                    ->label('Generar Borrador Word')
                    ->icon('heroicon-o-pencil-square')
                    ->modalDescription('Genera un borrador inicial en Word con placeholders <<<COMPLETAR>>> donde falten datos. Usted debe revisar, completar, verificar normas citadas y firmar antes de presentar. La IA puede equivocarse - usted es responsable del documento final.')
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
                        Select::make('ai_model')
                            ->label('Modelo de IA')
                            ->options(fn (): array => app(AIModelRegistry::class)->options())
                            ->default(fn (): ?string => session('ai_model'))
                            ->placeholder('Automatico (el primero disponible)')
                            ->helperText($this->aiDataNotice()),
                    ])
                    ->action(function (array $data) {
                        try {
                            session(['ai_model' => $data['ai_model'] ?? null]);
                            $content = app(AIService::class)
                                ->usingModel($data['ai_model'] ?? null)
                                ->draftDocument($this->record, $data['document_type']);
                        } catch (\Exception $e) {
                            Log::error('AI draft error: '.$e->getMessage());
                            Notification::make()->title('Error de IA')->body($e->getMessage())->danger()->send();

                            return;
                        }

                        if (! $content) {
                            Notification::make()
                                ->title('No se pudo generar el borrador')
                                ->body('Ningun modelo de IA respondio. Intente con otro modelo en unos minutos o revise storage/logs/laravel.log.')
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        try {
                            $filePath = app(DocumentGenerator::class)->generateWord(
                                $this->record,
                                $data['document_type'],
                                $content
                            );
                        } catch (\Exception $e) {
                            Log::error('Word generation error: '.$e->getMessage());
                            Notification::make()->title('Error generando Word')->body($e->getMessage())->danger()->send();

                            return;
                        }

                        $fileName = basename($filePath);

                        $this->js('window.location.href = '.json_encode(route('download.file', $fileName)));

                        Notification::make()->title('Borrador generado')->body('La descarga iniciara automaticamente.')->success()->send();
                    }),
            ])
                ->label('Asistente IA')
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->button(),
            ActionGroup::make([
                Action::make('download_report')
                    ->label('Descargar Reporte PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function () {
                        try {
                            $case = $this->record->load(['client', 'user', 'user.firm', 'caseType', 'flowProgress.flowStep']);
                            $firm = $case->user?->firm;

                            $actuaciones = CaseEvent::where('legal_case_id', $case->id)
                                ->where('event_date', '>=', now()->subDays(30))
                                ->orderBy('event_date')
                                ->get();

                            $vencimientos = Reminder::where('legal_case_id', $case->id)
                                ->where('is_completed', false)
                                ->where('due_date', '>=', now())
                                ->orderBy('due_date')
                                ->limit(10)
                                ->get();

                            $syncCount = TybaSyncLog::where('legal_case_id', $case->id)
                                ->where('created_at', '>=', now()->subDays(30))
                                ->count();

                            $pdf = Pdf::loadView('reports.monthly-case-report', [
                                'case' => $case,
                                'client' => $case->client,
                                'firm' => $firm,
                                'periodo' => now()->format('F Y'),
                                'generated_at' => now(),
                                'actuaciones' => $actuaciones,
                                'vencimientos' => $vencimientos,
                                'flowProgress' => $case->flowProgress->sortBy('flowStep.order'),
                                'resumen' => [
                                    'nuevas_actuaciones' => $actuaciones->count(),
                                    'recordatorios_pendientes' => $vencimientos->count(),
                                    'sincronizaciones' => $syncCount,
                                ],
                            ])->setPaper('letter');

                            $fileName = "reporte_{$case->case_number}_".now()->format('Y_m_d').'.pdf';
                            $path = app(GeneratedFileStore::class)->pathFor($case->firm_id, $fileName);

                            $pdf->save($path);
                            $this->js('window.location.href = '.json_encode(route('download.file', basename($path))));
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al generar reporte')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('sync_tyba')
                    ->authorize(fn (): bool => $this->userCanOnCase('events.create'))
                    ->label('Sincronizar Rama Judicial')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn () => (bool) $this->record->external_case_number)
                    ->requiresConfirmation()
                    ->modalHeading('Sincronizar con Rama Judicial')
                    ->modalDescription(fn () => "Se consultara el radicado {$this->record->external_case_number} en la API de la Rama Judicial para actualizar datos del proceso y registrar nuevas actuaciones.")
                    ->modalSubmitActionLabel('Sincronizar')
                    ->action(function () {
                        $tyba = app(TybaService::class);
                        $info = $tyba->extractProcessInfo($this->record->external_case_number);

                        if (! $info) {
                            TybaSyncLog::create([
                                'legal_case_id' => $this->record->id,
                                'status' => 'error',
                                'mensaje' => 'No se pudo consultar el radicado',
                                'origen' => 'manual',
                            ]);

                            Notification::make()
                                ->title('No se pudo consultar')
                                ->body('Verifique que el radicado sea correcto.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Actualizar datos del caso
                        $updates = [
                            'last_tyba_sync' => now(),
                            'tyba_data' => collect($info)->except(['sujetos', 'actuaciones'])->toArray(),
                        ];

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

                        TybaSyncLog::create([
                            'legal_case_id' => $this->record->id,
                            'status' => $newCount > 0 ? 'ok' : 'sin_cambios',
                            'nuevas_actuaciones' => $newCount,
                            'mensaje' => $newCount > 0
                                ? "{$newCount} nueva(s) actuacion(es) de {$totalActuaciones} totales"
                                : "Sin novedades. {$totalActuaciones} actuaciones verificadas",
                            'origen' => 'manual',
                        ]);

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
            ])
                ->label('Acciones')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->button(),
            ActionGroup::make([
                Action::make('compartir')
                    ->authorize(fn (): bool => $this->userCanOnCase('portal.share'))
                    ->label('Compartir con Cliente')
                    ->icon('heroicon-o-share')
                    ->modalWidth('lg')
                    ->modalHeading('Compartir Portal con Cliente')
                    ->modalSubmitActionLabel('Copiar enlace')
                    ->modalCancelActionLabel('Cerrar')
                    ->form(function () {
                        $record = $this->record;

                        // Ensures the portal is enabled and a token exists,
                        // without invalidating links that were previously shared.
                        $record->generatePortalToken();
                        $record->refresh();

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
                    ->authorize(fn (): bool => $this->userCanOnCase('portal.share'))
                    ->label(fn () => $this->record->portal_enabled ? 'Desactivar Portal' : 'Activar Portal')
                    ->icon(fn () => $this->record->portal_enabled ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn () => $this->record->portal_enabled ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn () => $this->record->portal_enabled ? 'Desactivar portal del cliente' : 'Activar portal del cliente')
                    ->modalDescription(fn () => $this->record->portal_enabled
                        ? 'El cliente ya no podra ver el estado de su caso.'
                        : 'El cliente podra ver el estado de su caso a traves del enlace.')
                    ->action(function () {
                        $record = $this->record;

                        if (! $record->portal_token) {
                            $record->generatePortalToken();
                        } else {
                            $record->update(['portal_enabled' => ! $record->portal_enabled]);
                        }

                        $status = $record->fresh()->portal_enabled ? 'activado' : 'desactivado';
                        Notification::make()->title("Portal {$status}")->success()->send();
                    }),
                Action::make('toggle_auto_report')
                    ->authorize(fn (): bool => $this->userCanOnCase('case.edit'))
                    ->label(fn () => $this->record->auto_report_enabled ? 'Desactivar Reporte Mensual' : 'Activar Reporte Mensual')
                    ->icon(fn () => $this->record->auto_report_enabled ? 'heroicon-o-envelope-open' : 'heroicon-o-envelope')
                    ->color(fn () => $this->record->auto_report_enabled ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn () => $this->record->auto_report_enabled ? 'Desactivar reporte mensual' : 'Activar reporte mensual')
                    ->modalDescription(fn () => $this->record->auto_report_enabled
                        ? 'El cliente dejara de recibir el reporte mensual por correo electronico.'
                        : "El dia 1 de cada mes se enviara automaticamente un PDF con el resumen del caso al correo del cliente ({$this->record->client->email}). Incluye actuaciones, flujo procesal y vencimientos.")
                    ->action(function () {
                        $this->record->update(['auto_report_enabled' => ! $this->record->auto_report_enabled]);
                        $status = $this->record->fresh()->auto_report_enabled ? 'activado' : 'desactivado';

                        Notification::make()->title("Reporte mensual {$status}")
                            ->body($this->record->auto_report_enabled
                                ? "El dia 1 de cada mes se enviara un PDF al correo del cliente ({$this->record->client->email})."
                                : 'No se enviaran reportes automaticos para este caso.')
                            ->success()
                            ->send();
                    }),
            ])
                ->label('Cliente')
                ->icon('heroicon-o-user-circle')
                ->color('info')
                ->button(),
            EditAction::make()
                ->label('Editar'),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    /**
     * Campos del modal de resultado de IA, con selector de modelo para regenerar.
     *
     * @param  'summarizeCase'|'suggestNextStep'  $task
     * @return array<int, mixed>
     */
    private function aiResultSchema(string $task, int $rows, string $failureMessage): array
    {
        // Nunca enviar datos del caso al proveedor si el usuario no puede usar la IA en este caso.
        if (! $this->userCanOnCase('ai.use')) {
            return [];
        }

        $generate = function (?string $modelKey) use ($task, $failureMessage): array {
            $ai = app(AIService::class)->usingModel($modelKey);
            $result = $ai->{$task}($this->record);
            $usedKey = $ai->getLastModelKey();

            $meta = 'Generado con '.($ai->getLastProvider() ?? 'N/A').' | '.now()->format('d/m/Y H:i');

            if ($modelKey && $usedKey && $usedKey !== $modelKey) {
                $meta .= ' (el modelo elegido no respondio, se uso otro disponible)';
            }

            return ['result' => $result ?? $failureMessage, 'meta' => $meta, 'model' => $usedKey ?? $modelKey];
        };

        $initial = $generate(session('ai_model'));

        return [
            Textarea::make('ai_result')
                ->label('')
                ->default($initial['result'])
                ->rows($rows)
                ->readOnly(),
            Select::make('ai_model')
                ->label('Modelo de IA')
                ->options(fn (): array => app(AIModelRegistry::class)->options())
                ->default($initial['model'])
                ->selectablePlaceholder(false)
                ->live()
                ->afterStateUpdated(function (?string $state, Set $set) use ($generate): void {
                    session(['ai_model' => $state]);
                    $regenerated = $generate($state);
                    $set('ai_result', $regenerated['result']);
                    $set('ai_meta', $regenerated['meta']);
                })
                ->helperText($this->aiDataNotice()),
            Hidden::make('ai_meta')
                ->default($initial['meta']),
            Placeholder::make('provider')
                ->label('')
                ->content(fn (Get $get): string => (string) $get('ai_meta')),
        ];
    }

    private function aiDataNotice(): HtmlString
    {
        return new HtmlString(
            '<span class="text-xs text-gray-400">Para generar este contenido, los datos del caso se procesan con un proveedor de IA externo. '
            .'<a href="'.route('portal.terms').'#ia" target="_blank" class="underline hover:text-gray-600">Mas informacion</a></span>'
        );
    }

    /**
     * Permiso por caso del usuario (los administradores de la firma siempre lo tienen).
     */
    private function userCanOnCase(string $permission): bool
    {
        return (bool) auth()->user()?->hasCasePermission($this->record->getKey(), $permission);
    }
}
