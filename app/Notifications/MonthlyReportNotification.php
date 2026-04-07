<?php

namespace App\Notifications;

use App\Models\LegalCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MonthlyReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public LegalCase $case,
        public string $pdfPath,
        public string $periodo,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
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
            ]);
    }
}
