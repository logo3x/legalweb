<?php

namespace App\Notifications;

use App\Models\LegalCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TybaSyncNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public LegalCase $case,
        public int $newActuaciones,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nuevas actuaciones detectadas - {$this->case->case_number}")
            ->greeting("Dr(a). {$notifiable->name}")
            ->line("Se detectaron **{$this->newActuaciones} nueva(s) actuacion(es)** en su caso:")
            ->line("Caso: **{$this->case->title}** ({$this->case->case_number})")
            ->line('Radicado: '.$this->case->external_case_number)
            ->line('Fuente: Rama Judicial de Colombia (Tyba)')
            ->line('Las actuaciones han sido registradas automaticamente en el expediente digital.')
            ->action('Ver caso', url("/admin/legal-cases/{$this->case->id}"))
            ->salutation('LegalWeb - Control inteligente de tus procesos legales');
    }
}
