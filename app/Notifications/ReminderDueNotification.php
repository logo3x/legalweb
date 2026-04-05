<?php

namespace App\Notifications;

use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Reminder $reminder) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $type = match ($this->reminder->type) {
            'audiencia' => 'Audiencia',
            'vencimiento' => 'Vencimiento de termino',
            'reunion' => 'Reunion',
            'tarea' => 'Tarea',
            default => 'Recordatorio',
        };

        $mail = (new MailMessage)
            ->subject("[Recordatorio] {$this->reminder->title}")
            ->greeting("Dr(a). {$notifiable->name}")
            ->line('Tiene un recordatorio programado:')
            ->line("**{$this->reminder->title}**")
            ->line("Tipo: {$type}")
            ->line("Fecha limite: {$this->reminder->due_date->format('d/m/Y H:i')}");

        if ($this->reminder->description) {
            $mail->line($this->reminder->description);
        }

        if ($this->reminder->legal_case_id) {
            $mail->action('Ver caso', url("/admin/legal-cases/{$this->reminder->legal_case_id}"));
        } else {
            $mail->action('Ver agenda', url('/admin/reminders'));
        }

        return $mail->salutation('LegalWeb - Control inteligente de tus procesos legales');
    }
}
