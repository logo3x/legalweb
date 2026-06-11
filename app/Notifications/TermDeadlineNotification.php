<?php

namespace App\Notifications;

use App\Models\CaseFlowProgress;
use App\Models\LegalCase;
use Filament\Actions\Action;
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
        $this->progress->loadMissing('flowStep');
        $stepName = $this->progress->flowStep->name ?? 'Etapa procesal';

        $pill = match (true) {
            $this->daysRemaining <= 0 => 'VENCE HOY',
            $this->daysRemaining === 1 => 'VENCE MA&Ntilde;ANA',
            default => 'VENCE EN '.$this->daysRemaining.' D&Iacute;AS H&Aacute;BILES',
        };

        $dueDate = now()->addWeekdays(max(0, $this->daysRemaining));

        return (new MailMessage)
            ->subject('[Termino procesal] '.$this->case->case_number.' - '.$stepName)
            ->view('emails.vencimiento', [
                'lawyerName' => 'Dr(a). '.($notifiable->name ?? ''),
                'pillText' => $pill,
                'headline' => 'Un t&eacute;rmino procesal del caso est&aacute; pr&oacute;ximo a vencer',
                'title' => $stepName,
                'dueAt' => $dueDate->translatedFormat('d \\d\\e F \\d\\e Y'),
                'priorityLabel' => $this->daysRemaining <= 1 ? 'Urgente' : 'Alta',
                'caseNumber' => $this->case->case_number,
                'despacho' => $this->case->court,
                'description' => $this->case->title.' · Por favor tome las acciones necesarias para evitar el vencimiento del termino.',
                'ctaUrl' => url("/admin/legal-cases/{$this->case->id}"),
                'ctaLabel' => 'Ver caso',
            ]);
    }
}
