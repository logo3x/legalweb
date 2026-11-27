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
            ->salutation('Mensaje automático enviado por LegalWeb. Por favor no responda a este correo.')
            // Datos para la plantilla (logo y nombre de la firma); with() los agregaria como lineas del texto.
            ->when(true, function (MailMessage $mail): void {
                $mail->viewData = [...($this->case->user?->firm?->emailBrand() ?? []), 'audience' => 'client'];
            });
    }
}
