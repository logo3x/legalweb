<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo de bienvenida inmediato al registrarse: resume lo que LegalWeb hace por el abogado
 * y lo invita a importar su primer proceso. Para miembros invitados el texto se adapta.
 */
class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public bool $isNewFirm = true,
        public ?string $firmName = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->isNewFirm
                ? 'Bienvenido a LegalWeb: sus procesos vigilados desde hoy'
                : 'Bienvenido a '.($this->firmName ?: 'su firma').' en LegalWeb')
            ->greeting("Hola {$notifiable->name}");

        if ($this->isNewFirm) {
            $mail->line('Su cuenta está lista y tiene **3 meses con todas las funciones**, sin tarjeta. Esto es lo que LegalWeb hace por usted:');
        } else {
            $mail->line('Ya hace parte del equipo de **'.($this->firmName ?: 'su firma').'** en LegalWeb. Esto es lo que puede hacer desde hoy:');
        }

        return $mail
            ->line('📥 **Importe un proceso con solo el radicado**, de la Rama Judicial o de SAMAI. ¿Tiene muchos? Súbalos desde un Excel o pegue varios radicados a la vez.')
            ->line('🔄 **Vigilancia diaria:** cada madrugada consultamos sus procesos y le avisamos por correo cuando hay una actuación nueva.')
            ->line('⚠️ **Sin falsas tranquilidades:** si una consulta falla, se lo decimos ese mismo día; y cada lunes recibe un informe con la última consulta exitosa de cada proceso.')
            ->line('⏰ **Términos en días hábiles** con el calendario judicial colombiano (festivos, Semana Santa y vacancia), con avisos 3 días antes, 1 día antes y el día del vencimiento.')
            ->line('🤖 **Asistente IA** que resume el expediente y redacta borradores, ocultando nombres, documentos y radicado antes de enviarlos.')
            ->line('🔍 **Búsqueda en un solo campo** por nombre, cédula/NIT o radicado en la Rama Judicial y SAMAI.')
            ->action('Importar mi primer proceso', url('/admin/legal-cases'))
            ->line('Toma menos de un minuto: entre a **Casos**, pulse **«Importar proceso»** y escriba el radicado.')
            ->line('¿Dudas? Escríbanos a legalwebco@gmail.com y le ayudamos a cargar sus procesos.');
    }
}
