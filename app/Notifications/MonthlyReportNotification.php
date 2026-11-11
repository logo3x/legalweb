<?php

namespace App\Notifications;

use App\Models\LegalCase;
use App\Notifications\Concerns\SendsWhatsApp;
use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MonthlyReportNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsWhatsApp;

    public function __construct(
        public LegalCase $case,
        public string $pdfPath,
        public string $periodo,
    ) {}

    public function via(object $notifiable): array
    {
        if ($this->case->is_demo) {
            return ['mail'];
        }

        return $this->withWhatsApp(['mail'], $notifiable);
    }

    /**
     * Aviso corto por WhatsApp: el PDF solo viaja por correo.
     */
    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $sender = $this->case->senderName();

        return new WhatsAppMessage(
            template: 'monthly.report.customer',
            variables: [
                1 => $notifiable->full_name,
                2 => $this->case->case_number,
                3 => $this->periodo,
                4 => $sender,
            ],
            fallbackText: "{$sender} - Estimado(a) {$notifiable->full_name}, el reporte mensual de su caso {$this->case->case_number} correspondiente a {$this->periodo} ya esta disponible. Lo enviamos a su correo electronico.",
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $firmName = $this->case->user?->firm?->name ?? 'LegalWeb';
        $lawyerName = $this->case->user?->name ?? 'su abogado';

        return (new MailMessage)
            ->subject("Reporte mensual de su caso - {$this->periodo}")
            ->greeting("Estimado(a) {$notifiable->first_name} {$notifiable->last_name}")
            ->line("Le compartimos el reporte mensual de su caso **{$this->case->case_number}** correspondiente al periodo **{$this->periodo}**.")
            ->line('En el documento adjunto encontrara:')
            ->line('- Estado actual del caso y flujo procesal')
            ->line('- Actuaciones registradas durante el mes')
            ->line('- Proximos vencimientos y recordatorios')
            ->line("Si tiene alguna pregunta, no dude en contactar a {$lawyerName}.")
            ->salutation("{$firmName} - Control inteligente de sus procesos legales")
            ->attach($this->pdfPath, [
                'as' => "Reporte_{$this->case->case_number}_{$this->periodo}.pdf",
                'mime' => 'application/pdf',
            ])
            ->with($this->case->user?->firm?->emailBrand() ?? []);
    }
}
