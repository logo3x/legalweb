<?php

namespace App\Notifications;

use App\Models\LegalCase;
use App\Notifications\Concerns\SendsWhatsApp;
use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PortalAccessNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsWhatsApp;

    public function __construct(
        public LegalCase $case,
        public string $ipAddress,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->withWhatsApp(['mail'], $notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Portal accedido - {$this->case->case_number}")
            ->greeting("Dr(a). {$notifiable->name}")
            ->line("Su cliente ha accedido al portal del caso **{$this->case->title}** ({$this->case->case_number}).")
            ->line('Fecha: '.now()->format('d/m/Y H:i'))
            ->line("IP: {$this->ipAddress}")
            ->line('El cliente acepto los terminos y condiciones de uso del portal.')
            ->action('Ver caso', url("/admin/legal-cases/{$this->case->id}"))
            ->salutation('LegalWeb - Control inteligente de sus procesos legales')
            ->with($this->case->user?->firm?->emailBrand() ?? []);
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $client = $this->case->client?->full_name ?? 'Su cliente';
        $when = now()->format('d/m/Y H:i');

        return new WhatsAppMessage(
            template: 'portal.access.lawyer',
            variables: [1 => $notifiable->name, 2 => $client, 3 => $when],
            fallbackText: "LegalWeb - {$client} accedio al portal del caso {$this->case->case_number} el {$when}.",
        );
    }
}
