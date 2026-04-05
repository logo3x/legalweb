<?php

namespace App\Notifications;

use App\Models\CaseFlowProgress;
use App\Models\LegalCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FlowStepCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public LegalCase $case,
        public CaseFlowProgress $progress,
        public ?string $nextStepName = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $portalUrl = $this->case->portal_token
            ? route('portal.show', $this->case->portal_token)
            : null;

        $firmName = $this->case->user->firm?->name ?? 'Su abogado';
        $stepName = $this->progress->flowStep->name;

        $mail = (new MailMessage)
            ->subject("Avance en su caso - {$this->case->case_number}")
            ->greeting("Estimado(a) {$this->case->client->full_name}")
            ->line("Le informamos que se ha completado una etapa en su caso **{$this->case->title}**:")
            ->line("Etapa completada: **{$stepName}**");

        if ($this->nextStepName) {
            $mail->line("Siguiente etapa: **{$this->nextStepName}**");
        }

        if ($portalUrl) {
            $mail->action('Ver progreso de mi caso', $portalUrl);
        }

        return $mail
            ->line("Atentamente, {$firmName}")
            ->salutation('Este es un mensaje automatico de LegalWeb. No responda a este correo.');
    }
}
