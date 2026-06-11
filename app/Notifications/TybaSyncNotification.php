<?php

namespace App\Notifications;

use App\Models\LegalCase;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TybaSyncNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public LegalCase $case,
        public int $newActuaciones,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title("{$this->newActuaciones} nueva(s) actuacion(es)")
            ->body("Caso {$this->case->case_number} - Rama Judicial detecto novedades")
            ->icon('heroicon-o-arrow-path')
            ->iconColor('warning')
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
        $this->case->loadMissing(['client', 'caseType', 'events' => fn ($q) => $q->orderByDesc('event_date')->limit(1)]);
        $latestEvent = $this->case->events->first();

        return (new MailMessage)
            ->subject("Nueva actuacion detectada - {$this->case->case_number}")
            ->view('emails.nueva-actuacion', [
                'lawyerName' => 'Dr(a). '.($notifiable->name ?? ''),
                'newCount' => $this->newActuaciones,
                'eventTitle' => $latestEvent?->title ?? 'Nueva actuacion en la Rama Judicial',
                'eventType' => $latestEvent?->event_type ?? 'actuacion',
                'eventDate' => $latestEvent?->event_date?->translatedFormat('d M Y'),
                'radicado' => $this->case->external_case_number ?? $this->case->case_number,
                'despacho' => $this->case->court ?? '-',
                'clientName' => $this->case->client?->full_name ?? '-',
                'caseTitle' => $this->case->case_number.' · '.($this->case->title ?? ''),
                'caseUrl' => url("/admin/legal-cases/{$this->case->id}"),
            ]);
    }
}
