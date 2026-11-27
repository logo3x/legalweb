<?php

namespace App\Notifications;

use App\Models\CaseEvent;
use App\Models\LegalCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public LegalCase $case,
        public CaseEvent $event,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Solo se enlaza el portal si esta activo; si no, el cliente llegaria a una pagina cerrada.
        $portalUrl = $this->case->portal_enabled && $this->case->portal_token
            ? route('portal.show', $this->case->portal_token)
            : null;

        $firmName = $this->case->user->firm?->name ?? 'Su abogado';

        $mail = (new MailMessage)
            ->subject("Actualización en su caso - {$this->case->case_number}")
            ->greeting("Estimado(a) {$this->case->client->full_name}")
            ->line("Le informamos que hay una nueva actuación en su caso **{$this->case->title}**:")
            ->line("**{$this->event->title}**")
            ->line('Tipo: '.(self::EVENT_TYPES[$this->event->event_type] ?? ucfirst((string) $this->event->event_type)).' | Fecha: '.$this->eventDate());

        foreach ($this->clientParagraphs() as $paragraph) {
            $mail->line($paragraph);
        }

        if ($this->event->rama_documents) {
            $mail->line('El despacho publicó documentos en esta actuación. Si los necesita, pídaselos a su abogado.');
        }

        if ($portalUrl) {
            $mail->action('Ver estado de mi caso', $portalUrl);
        }

        return $mail
            ->line("Atentamente, {$firmName}")
            ->salutation('Mensaje automático enviado por LegalWeb. Por favor no responda a este correo.')
            // Datos para la plantilla (logo y nombre de la firma); with() los agregaria como lineas del texto.
            ->when(true, function (MailMessage $mail): void {
                $mail->viewData = [...($this->case->user?->firm?->emailBrand() ?? []), 'audience' => 'client'];
            });
    }

    /**
     * @var array<string, string>
     */
    private const EVENT_TYPES = [
        'actuacion' => 'Actuación',
        'audiencia' => 'Audiencia',
        'notificacion' => 'Notificación',
        'memorial' => 'Memorial',
        'auto' => 'Auto',
        'sentencia' => 'Sentencia',
    ];

    /**
     * Las actuaciones de la Rama se guardan a medianoche: sin hora real, solo se muestra el dia.
     */
    private function eventDate(): string
    {
        $date = $this->event->event_date;

        return $date->format('H:i') === '00:00' ? $date->format('d/m/Y') : $date->format('d/m/Y H:i');
    }

    /**
     * Parrafos de la descripcion que tienen sentido para el cliente: se quitan las notas internas
     * para el abogado (avisos de documentos en LegalWeb, lineas de sincronizacion).
     *
     * @return list<string>
     */
    private function clientParagraphs(): array
    {
        return collect(preg_split('/\R{2,}/', (string) $this->event->description) ?: [])
            ->map(fn (string $paragraph): string => trim($paragraph))
            ->reject(fn (string $paragraph): bool => $paragraph === ''
                || str_starts_with($paragraph, 'Tiene documentos anexos en la Rama Judicial')
                || str_starts_with($paragraph, 'Tiene documentos asociados en la Rama Judicial')
                || str_starts_with($paragraph, 'Sincronizado desde Rama Judicial')
                || str_starts_with($paragraph, 'Sincronizado automaticamente')
                || str_starts_with($paragraph, 'Importado desde Rama Judicial'))
            ->values()
            ->all();
    }
}
