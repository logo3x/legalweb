<?php

namespace App\Notifications\Concerns;

use App\Models\Firm;
use App\Notifications\Channels\WhatsAppChannel;
use App\Services\WhatsAppService;

trait SendsWhatsApp
{
    /**
     * Agrega el canal de WhatsApp cuando el destinatario lo autorizo, tiene un
     * numero al que enviar y Twilio esta configurado.
     *
     * El numero sale de routeNotificationForWhatsapp() del destinatario: en
     * User es whatsapp_phone y en Client es phone. Los registros de ejemplo
     * (is_demo) nunca reciben WhatsApp.
     *
     * @param  array<int, string>  $channels
     * @return array<int, string>
     */
    protected function withWhatsApp(array $channels, object $notifiable): array
    {
        if (($notifiable->whatsapp_enabled ?? false)
            && ! ($notifiable->is_demo ?? false)
            && method_exists($notifiable, 'routeNotificationFor')
            && filled($notifiable->routeNotificationFor('whatsapp', $this))
            && $this->firmPlanAllowsWhatsApp($notifiable)
            && app(WhatsAppService::class)->isConfigured()) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }

    /**
     * El plan Gratuito (despues de la prueba) no incluye WhatsApp.
     */
    protected function firmPlanAllowsWhatsApp(object $notifiable): bool
    {
        $firm = isset($notifiable->firm_id) ? Firm::find($notifiable->firm_id) : null;

        return ! $firm || $firm->planLimits()->hasWhatsApp();
    }
}
