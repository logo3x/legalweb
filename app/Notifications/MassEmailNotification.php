<?php

namespace App\Notifications;

use App\Models\MassEmailCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mime\Email;

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
        $unsubscribeUrl = URL::signedRoute('mass-email.unsubscribe', ['user' => $notifiable->getKey()]);

        // Si el body trae HTML rico (h2, p, ul, etc.), lo renderizamos en la
        // plantilla branded mass-campaign. Si es texto plano antiguo, fallback
        // al MailMessage->line() de siempre para no romper campanas viejas.
        $isHtml = preg_match('/<(h2|h3|p|ul|ol|div|a|strong|table)\b/i', $body) === 1;

        // Reemplazo de variables: {{name}}, {{first_name}}, {{email}}, {{firm}},
        // {{site_url}}, {{login_url}}. Las nuevas plantillas las usan; las antiguas
        // siguen funcionando porque solo afecta donde aparezcan literalmente.
        // Los valores los escribe cada usuario (nombre, firma): en HTML se escapan.
        $values = [
            $name,
            explode(' ', trim($name))[0] ?? '',
            $notifiable->email ?? '',
            $notifiable->firm?->name ?? '',
            url('/'),
            url('/admin/login'),
        ];

        $body = str_replace(
            ['{{name}}', '{{first_name}}', '{{email}}', '{{firm}}', '{{site_url}}', '{{login_url}}'],
            $isHtml ? array_map(fn (string $value): string => e($value), $values) : $values,
            $body
        );

        if ($isHtml) {
            return (new MailMessage)
                ->subject($this->campaign->subject)
                ->withSymfonyMessage(fn (Email $message) => $this->addUnsubscribeHeader($message, $unsubscribeUrl))
                ->view('emails.mass-campaign', [
                    'subject' => $this->campaign->subject,
                    'body' => $body,
                    'previewText' => $this->extractPreview($body),
                    'unsubscribeUrl' => $unsubscribeUrl,
                ]);
        }

        $mail = (new MailMessage)
            ->subject($this->campaign->subject)
            ->withSymfonyMessage(fn (Email $message) => $this->addUnsubscribeHeader($message, $unsubscribeUrl))
            ->greeting('Hola '.($name ?: 'colega'));

        foreach (preg_split('/\r?\n\r?\n/', trim($body)) as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph !== '') {
                $mail->line($paragraph);
            }
        }

        return $mail
            ->salutation('Atentamente,'."\n".'El equipo de LegalWeb')
            ->line("Si no quieres recibir mas campanas de LegalWeb, cancela tu suscripcion aqui: {$unsubscribeUrl}");
    }

    /**
     * Cabecera estandar que Gmail/Outlook muestran como "Cancelar suscripcion".
     */
    private function addUnsubscribeHeader(Email $message, string $unsubscribeUrl): void
    {
        $message->getHeaders()->addTextHeader('List-Unsubscribe', "<{$unsubscribeUrl}>");
    }

    private function extractPreview(string $html): string
    {
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', $text);

        return mb_substr(trim($text), 0, 120);
    }
}
