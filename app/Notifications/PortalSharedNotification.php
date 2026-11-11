<?php

namespace App\Notifications;

use App\Models\LegalCase;
use App\Notifications\Concerns\SendsWhatsApp;
use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Envio del enlace del portal al cliente por WhatsApp, a pedido del abogado.
 *
 * No se encola: el abogado espera ver en el momento si el envio funciono.
 */
class PortalSharedNotification extends Notification
{
    use Queueable, SendsWhatsApp;

    public function __construct(
        public LegalCase $case,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($this->case->is_demo || ! $this->case->portalUrl()) {
            return [];
        }

        return $this->withWhatsApp([], $notifiable);
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $sender = $this->case->senderName();
        $url = (string) $this->case->portalUrl();

        return new WhatsAppMessage(
            template: 'portal.shared.customer',
            variables: [
                1 => $notifiable->full_name,
                2 => $this->case->case_number,
                3 => $sender,
                4 => $url,
            ],
            fallbackText: "{$sender} - Estimado(a) {$notifiable->full_name}, puede consultar el estado de su caso {$this->case->case_number} en este enlace seguro: {$url}",
        );
    }
}
