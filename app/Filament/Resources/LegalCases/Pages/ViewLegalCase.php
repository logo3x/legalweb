<?php

namespace App\Filament\Resources\LegalCases\Pages;

use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\CaseEvent;
use App\Models\Reminder;
use App\Models\TybaSyncLog;
use App\Notifications\PortalSharedNotification;
use App\Services\AIModelRegistry;
use App\Services\AIService;
use App\Services\CaseSyncService;
use App\Services\DocumentGenerator;
use App\Services\FirmPlan;
use App\Services\GeneratedFileStore;
use App\Services\SamaiService;
use App\Services\UsageLimiter;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
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
use Illuminate\Support\Str;

class ViewLegalCase extends ViewRecord
{
    protected static string $resource = LegalCaseResource::class;

    /**
     * Titulo corto: el nombre completo del caso ya aparece en la ficha y en el celular ocupaba media pantalla.
     */
    public function getTitle(): string
    {
        return Str::limit((string) $this->record->title, 70);
    }

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
                            ->options(fn (): array => app(AIModelRegistry::class)->labeledOptions())
                            ->default(fn (): ?string => session('ai_model'))
                            ->placeholder('Automatico (el primero disponible)')
                            ->helperText($this->aiDataNotice()),
                    ])
                    ->action(function (array $data) {
                        if (! $this->aiAllowed()) {
                            return;
                        }

                        try {
                            session(['ai_model' => $data['ai_model'] ?? null]);
                            $content = app(AIService::class)
                                ->usingModel($data['ai_model'] ?? null)
                                ->draftDocument($this->record, $data['document_type']);
                        } catch (\Exception $e) {
                            Log::error('AI draft error: '.$e->getMessage());
                            Notification::make()->title('Error de IA')->body('No se pudo generar el borrador. Intente de nuevo en unos minutos.')->danger()->send();

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
                            Notification::make()->title('Error generando Word')->body('No se pudo crear el archivo. Intente de nuevo.')->danger()->send();

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
                    // El reporte incluye actuaciones y vencimientos: exige el mismo permiso que esas pestanas.
                    ->authorize(fn (): bool => $this->userCanOnCase('events.view'))
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

                            $fileName = "reporte_{$case->case_number}_".now()->format('Y_m_d').'_'.Str::random(16).'.pdf';
                            $path = app(GeneratedFileStore::class)->pathFor($case->firm_id, $fileName);

                            $pdf->save($path);
                            $this->js('window.location.href = '.json_encode(route('download.file', basename($path))));
                        } catch (\Exception $e) {
                            Log::error('Case report error: '.$e->getMessage());

                            Notification::make()
                                ->title('Error al generar reporte')
                                ->body('No se pudo generar el reporte. Intente de nuevo.')
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('sync_tyba')
                    ->authorize(fn (): bool => $this->userCanOnCase('events.create'))
                    ->label(fn (): string => $this->record->isSamai() ? 'Sincronizar SAMAI' : 'Sincronizar Rama Judicial')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn () => (bool) $this->record->external_case_number)
                    ->requiresConfirmation()
                    ->modalHeading(fn (): string => 'Sincronizar con '.$this->record->judicialSourceLabel())
                    ->modalDescription(fn () => $this->record->isSamai()
                        ? "Se consultara el radicado {$this->record->external_case_number} en SAMAI para detectar cambios de ponente, partes o estado. Las actuaciones se revisan en SAMAI."
                        : "Se consultara el radicado {$this->record->external_case_number} en la API de la Rama Judicial para actualizar datos del proceso y registrar nuevas actuaciones.")
                    ->modalSubmitActionLabel('Sincronizar')
                    ->action(function () {
                        if (! UsageLimiter::attempt('judicial')) {
                            return;
                        }

                        $log = app(CaseSyncService::class)->sync($this->record, 'manual', notify: false);
                        $this->record->refresh();

                        if ($log->status === 'error') {
                            Notification::make()
                                ->title('No se pudo consultar')
                                ->body('Verifique que el radicado sea correcto o intente mas tarde.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $detalleCampos = collect($log->cambios['campos'] ?? [])
                            ->map(fn (array $c): string => "{$c['campo']}: {$c['antes']} → {$c['despues']}")
                            ->join('. ');

                        $hasNews = $log->status === 'ok';

                        Notification::make()
                            ->title($hasNews ? 'Sincronizacion con novedades' : 'Caso actualizado')
                            ->body($log->mensaje.($detalleCampos ? '. '.$detalleCampos : '').($hasNews ? '. Vea el detalle en "Historial de Sincronizacion".' : ''))
                            ->status($hasNews ? 'success' : 'info')
                            ->persistent($hasNews)
                            ->send();
                    }),
                Action::make('open_official_source')
                    ->label(fn (): string => $this->record->isSamai() ? 'Ver actuaciones en SAMAI' : 'Abrir consulta de la Rama Judicial')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->visible(fn (): bool => (bool) $this->record->external_case_number)
                    ->url(fn (): string => $this->record->isSamai()
                        ? ($this->record->tyba_data['url'] ?? SamaiService::BASE_URL.'/Vistas/Casos/procesos.aspx')
                        : 'https://consultaprocesos.ramajudicial.gov.co/Procesos/NumeroRadicacion')
                    ->openUrlInNewTab(),
                Action::make('open_corte_constitucional')
                    ->label('Buscar tutela en la Corte Constitucional')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->visible(fn (): bool => (bool) $this->record->external_case_number)
                    ->url(fn (): string => 'https://www.corteconstitucional.gov.co/secretaria/consulta-y-tramite-de-procesos/tutela/buscador/expediente/'
                        .'2015-01-01/'.now()->toDateString().'/'.rawurlencode((string) $this->record->external_case_number).'/0/0')
                    ->openUrlInNewTab(),
                Action::make('open_siugj')
                    ->label('Consultar en SIUGJ (laboral)')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->visible(fn (): bool => (bool) $this->record->external_case_number && ! $this->record->isSamai())
                    ->url('https://siugj.ramajudicial.gov.co/principalPortal/consultarProceso.php')
                    ->openUrlInNewTab(),
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
                            Placeholder::make('share_buttons')
                                ->label('Enviar directamente')
                                ->content(fn (): HtmlString => $this->portalShareButtons($url)),
                        ];
                    })
                    ->extraModalFooterActions(fn (): array => [
                        Action::make('send_portal_whatsapp')
                            ->authorize(fn (): bool => $this->userCanOnCase('portal.share'))
                            ->label('Enviar al cliente por WhatsApp')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->color('success')
                            ->visible(fn (): bool => $this->canSendPortalByWhatsApp())
                            ->action(fn () => $this->sendPortalByWhatsApp()),
                    ])
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

    /**
     * El envio automatico por WhatsApp requiere la autorizacion del cliente,
     * Twilio configurado y que el caso no sea de ejemplo.
     */
    private function canSendPortalByWhatsApp(): bool
    {
        $client = $this->record->client;

        return $client
            && ! $this->record->is_demo
            && (bool) auth()->user()->firm?->planLimits()->hasWhatsApp()
            && filled($client->routeNotificationForWhatsapp())
            && app(WhatsAppService::class)->isConfigured();
    }

    /**
     * Envia el enlace del portal al WhatsApp del cliente y avisa al abogado si funciono.
     */
    private function sendPortalByWhatsApp(): void
    {
        $record = $this->record;
        $client = $record->client;

        if (! $this->canSendPortalByWhatsApp()) {
            Notification::make()
                ->title('No se puede enviar por WhatsApp')
                ->body('El cliente debe autorizar los avisos por WhatsApp y tener un telefono registrado.')
                ->danger()
                ->send();

            return;
        }

        if (! UsageLimiter::attempt('whatsapp-portal', 'case-'.$record->getKey())) {
            return;
        }

        $record->generatePortalToken();
        $notification = new PortalSharedNotification($record);
        $message = $notification->toWhatsApp($client);

        $sent = app(WhatsAppService::class)->sendTemplate(
            (string) $client->routeNotificationForWhatsapp(),
            $message->template,
            $message->variables,
            $message->fallbackText,
        );

        if ($sent) {
            Notification::make()
                ->title('Enlace enviado por WhatsApp')
                ->body("Se envio el enlace del portal a {$client->full_name}.")
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('No se pudo enviar el WhatsApp')
            ->body('Verifique el telefono del cliente o la configuracion de Twilio e intente de nuevo. Tambien puede usar el boton de WhatsApp del modal.')
            ->danger()
            ->send();
    }

    /**
     * Botones para enviar el enlace del portal por WhatsApp o correo.
     */
    private function portalShareButtons(string $url): HtmlString
    {
        $client = $this->record->client;
        $firmName = $this->record->user?->firm?->name ?? 'su abogado';
        $message = "Hola {$client?->full_name}, puede consultar el estado de su caso {$this->record->case_number} en este enlace seguro de {$firmName}: {$url}";

        $phone = WhatsAppService::normalizePhone($client?->phone);
        $whatsAppUrl = 'https://wa.me/'.($phone ? ltrim($phone, '+') : '').'?text='.rawurlencode($message);

        $buttonStyle = 'display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;color:#fff;margin-right:8px;';

        $html = '<a href="'.e($whatsAppUrl).'" target="_blank" rel="noopener" style="'.$buttonStyle.'background:#16a34a;">WhatsApp'.($phone ? '' : ' (elegir contacto)').'</a>';

        if ($client?->email) {
            $mailto = 'mailto:'.rawurlencode($client->email)
                .'?subject='.rawurlencode("Portal de su caso {$this->record->case_number}")
                .'&body='.rawurlencode($message);
            $html .= '<a href="'.e($mailto).'" style="'.$buttonStyle.'background:#3A86FF;">Correo</a>';
        }

        return new HtmlString($html);
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
            if (! $this->aiAllowed()) {
                return ['result' => 'Alcanzo el limite de consultas de IA. Revise "Mi Plan" o intente de nuevo mas tarde.', 'meta' => '', 'model' => $modelKey];
            }

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
                ->options(fn (): array => app(AIModelRegistry::class)->labeledOptions())
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
            '<span class="text-xs text-gray-400">Los datos personales (cliente, partes, documentos y contacto) se ocultan antes de enviar el caso al proveedor de IA y se restauran en el resultado. '
            .'<a href="'.route('portal.terms').'#ia" target="_blank" class="underline hover:text-gray-600">Mas informacion</a></span>'
        );
    }

    /**
     * Cupo de IA del plan (Gratuito: consultas al mes) y limite de frecuencia por usuario.
     */
    private function aiAllowed(): bool
    {
        $limits = auth()->user()->firm?->planLimits();

        if ($limits && ! $limits->canUseAi()) {
            Notification::make()
                ->title('Uso las consultas de IA de este mes')
                ->body(FirmPlan::upgradeMessage('El plan Gratuito incluye '.$limits->aiMonthlyLimit().' consultas de IA al mes.'))
                ->warning()
                ->persistent()
                ->send();

            return false;
        }

        return UsageLimiter::attempt('ai');
    }

    /**
     * Permiso por caso del usuario (los administradores de la firma siempre lo tienen).
     */
    private function userCanOnCase(string $permission): bool
    {
        return (bool) auth()->user()?->hasCasePermission($this->record->getKey(), $permission);
    }
}
