<?php

namespace App\Filament\Pages;

use App\Models\CaseEvent;
use App\Models\CaseFlowProgress;
use App\Models\Client;
use App\Models\Document;
use App\Models\Firm;
use App\Models\FlowStep;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Notifications\AutoRenewFailedNotification;
use App\Notifications\CaseUpdatedNotification;
use App\Notifications\ClientDocumentReadyNotification;
use App\Notifications\FlowStepCompletedNotification;
use App\Notifications\PortalAccessNotification;
use App\Notifications\ReminderDueNotification;
use App\Notifications\SyncFailuresNotification;
use App\Notifications\TermDeadlineNotification;
use App\Notifications\TybaSyncNotification;
use App\Notifications\VerificationReportNotification;
use App\Services\UsageLimiter;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class ProbarNotificaciones extends Page
{
    protected string $view = 'filament.pages.probar-notificaciones';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Probar avisos';

    protected static ?string $title = 'Probar Notificaciones';

    protected static ?int $navigationSort = 35;

    public function getNotifications(): array
    {
        return [
            [
                'key' => 'reminder_due',
                'label' => 'Recordatorio de agenda',
                'description' => 'Se envia cuando un recordatorio de su agenda esta proximo a vencer o llega su hora.',
                'icon' => 'heroicon-o-bell-alert',
                'color' => 'warning',
            ],
            [
                'key' => 'tyba_sync',
                'label' => 'Nueva actuacion en Rama Judicial',
                'description' => 'Se envia cuando la sincronizacion diaria detecta una nueva actuacion en alguno de sus casos.',
                'icon' => 'heroicon-o-arrow-path',
                'color' => 'info',
            ],
            [
                'key' => 'term_deadline',
                'label' => 'Termino procesal por vencer',
                'description' => 'Se envia cuando un termino del flujo procesal esta proximo a vencer (3 dias o menos).',
                'icon' => 'heroicon-o-clock',
                'color' => 'danger',
            ],
            [
                'key' => 'case_updated',
                'label' => 'Caso actualizado',
                'description' => 'Se envia al cliente desde el portal cuando se registra una nueva actuacion importante.',
                'icon' => 'heroicon-o-megaphone',
                'color' => 'primary',
            ],
            [
                'key' => 'flow_step_completed',
                'label' => 'Etapa de flujo completada',
                'description' => 'Se envia al cliente cuando se completa una etapa del flujo procesal de su caso.',
                'icon' => 'heroicon-o-check-badge',
                'color' => 'success',
            ],
            [
                'key' => 'portal_access',
                'label' => 'Acceso al portal del cliente',
                'description' => 'Se envia al abogado cuando un cliente ingresa al portal del caso por primera vez.',
                'icon' => 'heroicon-o-eye',
                'color' => 'gray',
            ],
            [
                'key' => 'client_document_ready',
                'label' => 'Cliente envió un documento',
                'description' => 'Se envía al abogado cuando un cliente confirma o envía un enlace de un documento solicitado.',
                'icon' => 'heroicon-o-document-check',
                'color' => 'success',
            ],
            [
                'key' => 'sync_failures',
                'label' => 'No pudimos revisar un proceso',
                'description' => 'Se envía al abogado el mismo día si la consulta a la Rama Judicial o SAMAI falla, para que lo revise a mano.',
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'danger',
            ],
            [
                'key' => 'verification_report',
                'label' => 'Informe de verificación de los lunes',
                'description' => 'Cada lunes: todos sus procesos con la fecha de la última consulta exitosa y las actuaciones nuevas de la semana.',
                'icon' => 'heroicon-o-clipboard-document-check',
                'color' => 'info',
            ],
            [
                'key' => 'auto_renew_failed',
                'label' => 'Cobro automático rechazado',
                'description' => 'Se envía al administrador si el banco no aprueba el cobro automático de la renovación con la tarjeta registrada.',
                'icon' => 'heroicon-o-credit-card',
                'color' => 'warning',
            ],
        ];
    }

    public function enviarPrueba(string $key): void
    {
        if (! UsageLimiter::attempt('test-notification')) {
            return;
        }

        $user = auth()->user();

        // Si la firma aun no tiene casos, se usa un caso de ejemplo en memoria (no se guarda nada).
        $case = ($user->firm_id ? LegalCase::where('firm_id', $user->firm_id)->orderByDesc('id')->first() : null)
            ?? $this->sampleCase($user);

        try {
            match ($key) {
                'reminder_due' => $this->testReminderDue($user, $case),
                'tyba_sync' => $user->notifyNow(new TybaSyncNotification($case, 3)),
                'term_deadline' => $this->testTermDeadline($user, $case),
                'case_updated' => $this->testCaseUpdated($user, $case),
                'flow_step_completed' => $this->testFlowStepCompleted($user, $case),
                'portal_access' => $user->notifyNow(new PortalAccessNotification($case, request()->ip() ?? '127.0.0.1')),
                'client_document_ready' => $this->testClientDocumentReady($user, $case),
                'sync_failures' => $this->testSyncFailures($user, $case),
                'verification_report' => $this->testVerificationReport($user, $case),
                'auto_renew_failed' => $this->testAutoRenewFailed($user),
                default => null,
            };

            Notification::make()
                ->title('Correo de prueba enviado')
                ->body('Revise su bandeja de entrada en '.$user->email.'. Puede tardar hasta 1 minuto; si no aparece, busque en spam y márquelo como «No es spam».')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Log::error('Test notification error: '.$e->getMessage(), ['key' => $key, 'user_id' => $user->id]);

            Notification::make()
                ->title('Error enviando correo de prueba')
                ->body('Revise la configuracion e intente de nuevo.')
                ->danger()
                ->send();
        }
    }

    private function testReminderDue($user, LegalCase $case): void
    {
        $reminder = Reminder::where('user_id', $user->id)
            ->where('is_completed', false)
            ->first()
            ?? new Reminder([
                'firm_id' => $user->firm_id,
                'user_id' => $user->id,
                'legal_case_id' => $case->id,
                'title' => 'Audiencia de prueba',
                'description' => 'Este es un recordatorio de prueba. Su contenido real seria preparar alegatos, revisar pruebas, etc.',
                'type' => 'audiencia',
                'priority' => 'alta',
                'due_date' => now()->addDays(2),
                'remind_at' => now(),
            ]);

        $reminder->setRelation('legalCase', $case);
        $user->notifyNow(new ReminderDueNotification($reminder));
    }

    private function testTermDeadline($user, LegalCase $case): void
    {
        $progress = CaseFlowProgress::where('legal_case_id', $case->id)
            ->with('flowStep')
            ->first();

        $progress ??= $this->sampleProgress($case);

        $user->notifyNow(new TermDeadlineNotification($case, $progress, 3, now()->addWeekdays(3)));
    }

    private function testCaseUpdated($user, LegalCase $case): void
    {
        $event = CaseEvent::where('legal_case_id', $case->id)
            ->orderByDesc('event_date')
            ->first()
            ?? new CaseEvent([
                'legal_case_id' => $case->id,
                'title' => 'Auto fija fecha audiencia inicial',
                'event_type' => 'auto',
                'event_date' => now(),
                'description' => 'Esta es una actuacion de prueba. El contenido real vendria de la Rama Judicial.',
            ]);

        $user->notifyNow(new CaseUpdatedNotification($case, $event));
    }

    private function testFlowStepCompleted($user, LegalCase $case): void
    {
        $progress = CaseFlowProgress::where('legal_case_id', $case->id)
            ->with('flowStep')
            ->first();

        $progress ??= $this->sampleProgress($case);

        $user->notifyNow(new FlowStepCompletedNotification($case, $progress, 'Siguiente etapa de ejemplo'));
    }

    private function testClientDocumentReady($user, LegalCase $case): void
    {
        $document = Document::where('legal_case_id', $case->id)->first();

        if (! $document) {
            $document = new Document([
                'legal_case_id' => $case->id,
                'name' => 'Cedula de ciudadania (documento de prueba)',
                'responsible' => 'cliente',
                'status' => 'recibido',
                'priority' => 'alta',
            ]);
            $document->id = 0;
        }

        $user->notifyNow(new ClientDocumentReadyNotification($case, $document, 'uploaded'));
    }

    private function testSyncFailures($user, LegalCase $case): void
    {
        $failed = clone $case;
        $failed->sync_error = 'La Rama Judicial no respondio (HTTP 503)';
        $failed->sync_failed_at = now()->subHours(5);

        $user->notifyNow(new SyncFailuresNotification(new EloquentCollection([$failed])));
    }

    private function testVerificationReport($user, LegalCase $case): void
    {
        $radicado = (string) ($case->external_case_number ?: '11001310300120260012300');

        $rows = [
            ['case_number' => 'LW-0002-EJEMPLO', 'radicado' => '05001310500120260004500', 'title' => 'Proceso de ejemplo con error', 'state' => 'error', 'detail' => 'No se pudo consultar desde el '.now()->subDay()->format('d/m/Y H:i').': La Rama Judicial no respondio (HTTP 503)', 'new_events' => 0],
            ['case_number' => (string) $case->case_number, 'radicado' => $radicado, 'title' => (string) $case->title, 'state' => 'ok', 'detail' => 'Consultado el '.now()->subHours(6)->format('d/m/Y H:i'), 'new_events' => 2],
        ];

        $user->notifyNow(new VerificationReportNotification($rows, ['total' => 2, 'ok' => 1, 'error' => 1, 'stale' => 0, 'new_events' => 2]));
    }

    private function testAutoRenewFailed($user): void
    {
        $firm = $user->firm ? clone $user->firm : new Firm(['name' => 'Firma de ejemplo']);
        $firm->forceFill(['card_brand' => 'VISA', 'card_last_four' => '4242']);

        $user->notifyNow(new AutoRenewFailedNotification($firm, true));
    }

    /**
     * Caso de ejemplo en memoria para firmas sin casos (nunca se guarda).
     */
    private function sampleCase($user): LegalCase
    {
        $case = new LegalCase([
            'case_number' => 'LW-0001-EJEMPLO',
            'external_case_number' => '11001310300120260012300',
            'title' => 'Proceso ejecutivo de ejemplo - Banco vs Cliente',
            'court' => 'Juzgado 1 Civil del Circuito de Bogota',
            'status' => 'en_progreso',
            'priority' => 'alta',
            'firm_id' => $user->firm_id,
            'user_id' => $user->id,
        ]);
        $case->id = 0;
        $case->setRelation('user', $user);
        $case->setRelation('client', new Client(['first_name' => 'Cliente', 'last_name' => 'de ejemplo', 'email' => $user->email]));

        return $case;
    }

    private function sampleProgress(LegalCase $case): CaseFlowProgress
    {
        $progress = new CaseFlowProgress(['status' => 'en_progreso', 'started_at' => now()->subDays(2)]);
        $progress->id = 0;
        $progress->setRelation('flowStep', new FlowStep(['name' => 'Contestacion de la demanda', 'days_limit' => 10]));
        $progress->setRelation('legalCase', $case);

        return $progress;
    }
}
