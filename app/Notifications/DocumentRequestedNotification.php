<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\LegalCase;
use App\Notifications\Concerns\SendsWhatsApp;
use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Aviso al cliente de que el abogado le solicito un documento.
 *
 * Solo se envia por WhatsApp (con autorizacion del cliente): el correo al
 * cliente no tiene consentimiento previo y el abogado ya puede compartir el
 * portal por correo desde el caso.
 */
class DocumentRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsWhatsApp;

    public function __construct(
        public LegalCase $case,
        public Document $document,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($this->case->is_demo) {
            return [];
        }

        return $this->withWhatsApp([], $notifiable);
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $sender = $this->case->senderName();
        $url = $this->case->portalUrl();

        $fallback = "{$sender} - Estimado(a) {$notifiable->full_name}, para avanzar en su caso {$this->case->case_number} necesitamos el documento \"{$this->document->name}\".";
        $fallback .= $url
            ? " Puede consultar el detalle y enviarlo desde el portal de su caso: {$url}"
            : ' Por favor comuniquese con su abogado para hacerlo llegar.';

        return new WhatsAppMessage(
            template: 'document.requested.customer',
            variables: [
                1 => $notifiable->full_name,
                2 => $this->document->name,
                3 => $sender,
                4 => $url ?? 'comuniquese con su abogado',
            ],
            fallbackText: $fallback,
        );
    }
}
