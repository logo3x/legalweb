<?php

namespace App\Notifications;

use App\Models\Reminder;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
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
        return ['mail', 'database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $url = $this->reminder->legal_case_id
            ? url("/admin/legal-cases/{$this->reminder->legal_case_id}")
            : url('/admin/reminders');

        $color = match ($this->reminder->priority) {
            'urgente' => 'danger',
            'alta' => 'warning',
            default => 'info',
        };

        return FilamentNotification::make()
            ->title($this->reminder->title)
            ->body('Vence: '.$this->reminder->due_date->format('d/m/Y H:i'))
            ->icon('heroicon-o-bell-alert')
            ->iconColor($color)
            ->actions([
                Action::make('ver')
                    ->label('Ver detalle')
                    ->url($url)
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
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

        $brand = $notifiable->firm?->emailBrand() ?? [];

        return $mail
            ->salutation('LegalWeb - Control inteligente de sus procesos legales')
            ->with($brand);
    }
}
