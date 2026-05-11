<?php

namespace App\Filament\Pages;

use App\Models\CaseEvent;
use App\Models\CaseFlowProgress;
use App\Models\Document;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Notifications\CaseUpdatedNotification;
use App\Notifications\ClientDocumentReadyNotification;
use App\Notifications\FlowStepCompletedNotification;
use App\Notifications\PortalAccessNotification;
use App\Notifications\ReminderDueNotification;
use App\Notifications\TermDeadlineNotification;
use App\Notifications\TybaSyncNotification;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class ProbarNotificaciones extends Page
{
    protected string $view = 'filament.pages.probar-notificaciones';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Configuracion';

    protected static ?string $navigationLabel = 'Probar Notificaciones';

    protected static ?string $title = 'Probar Notificaciones';

    protected static ?int $navigationSort = 30;

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
                'label' => 'Cliente envio un documento',
                'description' => 'Se envia al abogado cuando un cliente confirma o envia un enlace de un documento solicitado.',
                'icon' => 'heroicon-o-document-check',
                'color' => 'success',
            ],
        ];
    }

    public function enviarPrueba(string $key): void
    {
        $user = auth()->user();

        $case = LegalCase::where('firm_id', $user->firm_id)
            ->orderByDesc('id')
            ->first();

        if (! $case) {
            Notification::make()
                ->title('Primero registre un caso')
                ->body('Para probar las notificaciones necesita tener al menos un caso registrado en su firma. Use "Importar desde Tyba" desde el modulo Casos.')
                ->warning()
                ->send();

            return;
        }

        try {
            match ($key) {
                'reminder_due' => $this->testReminderDue($user, $case),
                'tyba_sync' => $user->notify(new TybaSyncNotification($case, 3)),
                'term_deadline' => $this->testTermDeadline($user, $case),
                'case_updated' => $this->testCaseUpdated($user, $case),
                'flow_step_completed' => $this->testFlowStepCompleted($user, $case),
                'portal_access' => $user->notify(new PortalAccessNotification($case, request()->ip() ?? '127.0.0.1')),
                'client_document_ready' => $this->testClientDocumentReady($user, $case),
                default => null,
            };

            Notification::make()
                ->title('Correo de prueba enviado')
                ->body('Revise su bandeja de entrada en '.$user->email.'. Puede tardar hasta 1 minuto en llegar.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Log::error('Test notification error: '.$e->getMessage(), ['key' => $key, 'user_id' => $user->id]);

            Notification::make()
                ->title('Error enviando correo de prueba')
                ->body('Detalles: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function testReminderDue($user, LegalCase $case): void
    {
        $reminder = $user->reminders()
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
        $user->notify(new ReminderDueNotification($reminder));
    }

    private function testTermDeadline($user, LegalCase $case): void
    {
        $progress = CaseFlowProgress::where('legal_case_id', $case->id)
            ->with('flowStep')
            ->first();

        if (! $progress) {
            $reminder = new Reminder([
                'firm_id' => $user->firm_id,
                'user_id' => $user->id,
                'legal_case_id' => $case->id,
                'title' => 'Termino procesal de prueba',
                'description' => 'Etapa: Contestacion de demanda. Plazo: 3 dias habiles.',
                'type' => 'vencimiento',
                'priority' => 'urgente',
                'due_date' => now()->addDays(3),
                'remind_at' => now(),
            ]);
            $reminder->setRelation('legalCase', $case);
            $user->notify(new ReminderDueNotification($reminder));

            return;
        }

        $user->notify(new TermDeadlineNotification($case, $progress, 3));
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

        $user->notify(new CaseUpdatedNotification($case, $event));
    }

    private function testFlowStepCompleted($user, LegalCase $case): void
    {
        $progress = CaseFlowProgress::where('legal_case_id', $case->id)
            ->with('flowStep')
            ->first();

        if (! $progress) {
            Notification::make()
                ->title('Caso sin flujo procesal')
                ->body('Este caso no tiene flujo procesal asignado. Asigne uno desde la vista del caso para probar esta notificacion.')
                ->warning()
                ->send();

            return;
        }

        $user->notify(new FlowStepCompletedNotification($case, $progress, 'Siguiente etapa de ejemplo'));
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

        $user->notify(new ClientDocumentReadyNotification($case, $document, 'uploaded'));
    }
}
