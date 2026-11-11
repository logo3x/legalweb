<?php

namespace App\Notifications\Concerns;

use App\Notifications\Channels\WhatsAppChannel;
use App\Services\WhatsAppService;

trait SendsWhatsApp
{
    /**
     * Agrega el canal de WhatsApp cuando el usuario lo activo y Twilio esta configurado.
     *
     * @param  array<int, string>  $channels
     * @return array<int, string>
     */
    protected function withWhatsApp(array $channels, object $notifiable): array
    {
        if (($notifiable->whatsapp_enabled ?? false)
            && filled($notifiable->whatsapp_phone ?? null)
            && app(WhatsAppService::class)->isConfigured()) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }
}
