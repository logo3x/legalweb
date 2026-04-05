<?php

namespace App\Notifications;

use App\Models\CaseEvent;
use App\Models\LegalCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public LegalCase $case,
        public CaseEvent $event,
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

        $mail = (new MailMessage)
            ->subject("Actualizacion en su caso - {$this->case->case_number}")
            ->greeting("Estimado(a) {$this->case->client->full_name}")
            ->line("Le informamos que hay una nueva actuacion en su caso **{$this->case->title}**:")
            ->line("**{$this->event->title}**")
            ->line("Tipo: {$this->event->event_type} | Fecha: {$this->event->event_date->format('d/m/Y H:i')}");

        if ($this->event->description) {
            $mail->line($this->event->description);
        }

        if ($portalUrl) {
            $mail->action('Ver estado de mi caso', $portalUrl);
        }

        return $mail
            ->line("Atentamente, {$firmName}")
            ->salutation('Este es un mensaje automatico de LegalWeb. No responda a este correo.');
    }
}
