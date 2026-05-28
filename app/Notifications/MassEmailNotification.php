<?php

namespace App\Notifications;

use App\Models\MassEmailCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MassEmailNotification extends Notification
{
    use Queueable;

    public function __construct(public MassEmailCampaign $campaign) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $body = $this->campaign->body;
        $name = $notifiable->name ?? '';

        // Reemplazo simple de variables {{name}} y {{email}} para personalizacion basica.
        $body = str_replace(
            ['{{name}}', '{{email}}', '{{firm}}'],
            [$name, $notifiable->email ?? '', $notifiable->firm?->name ?? ''],
            $body
        );

        $mail = (new MailMessage)
            ->subject($this->campaign->subject)
            ->greeting('Hola '.($name ?: 'colega'));

        foreach (preg_split('/\r?\n\r?\n/', trim($body)) as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph !== '') {
                $mail->line($paragraph);
            }
        }

        return $mail->salutation('Atentamente,'."\n".'El equipo de LegalWeb');
    }
}
