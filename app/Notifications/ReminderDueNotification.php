<?php

namespace App\Notifications;

use App\Models\Reminder;
use Filament\Actions\Action;
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
        $this->reminder->loadMissing('legalCase');
        $case = $this->reminder->legalCase;

        $now = now();
        $due = $this->reminder->due_date;
        $diffDays = $due ? max(0, (int) $now->diffInDays($due, false)) : null;
        $pill = match (true) {
            $due && $due->isPast() => 'VENCIDO',
            $diffDays === 0 => 'VENCE HOY',
            $diffDays === 1 => 'VENCE MA&Ntilde;ANA',
            $diffDays !== null && $diffDays <= 7 => 'VENCE EN '.$diffDays.' D&Iacute;AS',
            default => 'RECORDATORIO PR&Oacute;XIMO',
        };

        $ctaUrl = $this->reminder->legal_case_id
            ? url("/admin/legal-cases/{$this->reminder->legal_case_id}")
            : url('/admin/reminders');

        $ctaLabel = $this->reminder->legal_case_id ? 'Ver caso' : 'Ver mi agenda';

        return (new MailMessage)
            ->subject('[Recordatorio] '.$this->reminder->title)
            ->view('emails.vencimiento', [
                'lawyerName' => 'Dr(a). '.($notifiable->name ?? ''),
                'pillText' => $pill,
                'headline' => 'Tienes un recordatorio activo en tu agenda',
                'title' => $this->reminder->title,
                'dueAt' => $due?->translatedFormat('d \\d\\e F \\d\\e Y · g:i a'),
                'priorityLabel' => ucfirst($this->reminder->priority ?? 'media'),
                'caseNumber' => $case?->case_number,
                'despacho' => $case?->court,
                'description' => $this->reminder->description,
                'ctaUrl' => $ctaUrl,
                'ctaLabel' => $ctaLabel,
            ]);
    }
}
