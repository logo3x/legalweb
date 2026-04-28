<?php

namespace App\Notifications;

use App\Models\CaseFlowProgress;
use App\Models\LegalCase;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TermDeadlineNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public LegalCase $case,
        public CaseFlowProgress $progress,
        public int $daysRemaining,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $color = $this->daysRemaining <= 1 ? 'danger' : ($this->daysRemaining <= 3 ? 'warning' : 'info');

        return FilamentNotification::make()
            ->title("Termino por vencer ({$this->daysRemaining} dias)")
            ->body($this->case->case_number.' - '.$this->progress->flowStep->name)
            ->icon('heroicon-o-clock')
            ->iconColor($color)
            ->actions([
                Action::make('ver')
                    ->label('Ver caso')
                    ->url(url("/admin/legal-cases/{$this->case->id}"))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $stepName = $this->progress->flowStep->name;
        $daysLimit = $this->progress->flowStep->days_limit;
        $urgency = $this->daysRemaining <= 1 ? 'URGENTE' : 'Atencion';

        return (new MailMessage)
            ->subject("[{$urgency}] Termino por vencer - {$this->case->case_number}")
            ->greeting("Dr(a). {$notifiable->name}")
            ->line("El siguiente termino esta proximo a vencer en el caso **{$this->case->title}** ({$this->case->case_number}):")
            ->line("Etapa: **{$stepName}**")
            ->line("Plazo: {$daysLimit} dias")
            ->line("Dias restantes: **{$this->daysRemaining}**")
            ->action('Ver caso', url("/admin/legal-cases/{$this->case->id}"))
            ->line('Por favor tome las acciones necesarias para evitar el vencimiento del termino.')
            ->salutation('LegalWeb - Control inteligente de tus procesos legales');
    }
}
