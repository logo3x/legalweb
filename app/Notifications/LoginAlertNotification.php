<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $ipAddress,
        public string $userAgent,
        public string $loginAt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $device = $this->parseUserAgent($this->userAgent);

        return (new MailMessage)
            ->subject('Inicio de sesion detectado en su cuenta de LegalWeb')
            ->greeting("Hola {$notifiable->name}")
            ->line('Detectamos un nuevo inicio de sesion en su cuenta de LegalWeb con los siguientes datos:')
            ->line("**Fecha y hora:** {$this->loginAt}")
            ->line("**IP de origen:** {$this->ipAddress}")
            ->line("**Navegador / dispositivo:** {$device}")
            ->action('Ir al panel', url('/admin'))
            ->line('Si fue usted, no necesita hacer nada.')
            ->line('**Si no reconoce este acceso**, le recomendamos cambiar su contrasena inmediatamente y revisar la actividad reciente de su cuenta.')
            ->line('Si desea dejar de recibir estas alertas, puede desactivarlas desde Mi Firma > Seguridad.')
            ->salutation('Atentamente,'."\n".'El equipo de seguridad de LegalWeb');
    }

    /**
     * Convierte un user agent crudo en algo legible como "Chrome en Windows".
     */
    private function parseUserAgent(string $ua): string
    {
        if ($ua === '' || $ua === 'unknown') {
            return 'Desconocido';
        }

        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') && ! str_contains($ua, 'Chromium') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'Navegador desconocido',
        };

        $os = match (true) {
            str_contains($ua, 'Windows NT 10') => 'Windows 10/11',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Mac OS X') => 'macOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'sistema desconocido',
        };

        return "{$browser} en {$os}";
    }
}
