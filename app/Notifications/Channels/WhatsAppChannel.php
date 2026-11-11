<?php

namespace App\Notifications\Channels;

use App\Notifications\Messages\WhatsAppMessage;
use App\Services\WhatsAppService;
use Illuminate\Notifications\Notification;

class WhatsAppChannel
{
    public function __construct(public WhatsAppService $whatsApp) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $phone = $notifiable->routeNotificationFor('whatsapp', $notification);

        if (! $phone || ! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);

        if (! $message instanceof WhatsAppMessage) {
            return;
        }

        $this->whatsApp->sendTemplate($phone, $message->template, $message->variables, $message->fallbackText);
    }
}
