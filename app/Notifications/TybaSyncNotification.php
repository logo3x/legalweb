<?php

namespace App\Notifications;

use App\Models\LegalCase;
use App\Notifications\Concerns\SendsWhatsApp;
use App\Notifications\Messages\WhatsAppMessage;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TybaSyncNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsWhatsApp;

    /**
     * @param  array{actuaciones?: array<int, array{fecha: string, tipo: string, anotacion: string}>, campos?: array<int, array{campo: string, antes: string, despues: string}>}  $cambios
     */
    public function __construct(
        public LegalCase $case,
        public int $newActuaciones,
        public array $cambios = [],
    ) {}

    public function via(object $notifiable): array
    {
        return $this->withWhatsApp(['mail', 'database'], $notifiable);
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->headline())
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
        $onlyFieldChanges = $this->newActuaciones === 0;

        return (new MailMessage)
            ->subject(($onlyFieldChanges ? 'Cambios en el proceso' : 'Nueva actuacion detectada')." - {$this->case->case_number}")
            ->view('emails.nueva-actuacion', [
                'lawyerName' => 'Dr(a). '.($notifiable->name ?? ''),
                'newCount' => $this->newActuaciones,
                'eventTitle' => $onlyFieldChanges
                    ? 'Cambiaron datos del proceso en la Rama Judicial'
                    : ($latestEvent?->title ?? 'Nueva actuacion en la Rama Judicial'),
                'eventType' => $latestEvent?->event_type ?? 'actuacion',
                'eventDate' => $latestEvent?->event_date?->translatedFormat('d M Y'),
                'radicado' => $this->case->external_case_number ?? $this->case->case_number,
                'despacho' => $this->case->court ?? '-',
                'clientName' => $this->case->client?->full_name ?? '-',
                'caseTitle' => $this->case->case_number.' · '.($this->case->title ?? ''),
                'caseUrl' => url("/admin/legal-cases/{$this->case->id}"),
                'fieldChanges' => $this->cambios['campos'] ?? [],
            ]);
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $url = url("/admin/legal-cases/{$this->case->id}");
        $first = $this->cambios['actuaciones'][0]['tipo']
            ?? ($this->cambios['campos'][0]['campo'] ?? 'Datos del proceso').' actualizado';
        $summary = $this->headline().': '.$first;

        return new WhatsAppMessage(
            template: 'tyba.new_event.lawyer',
            variables: [1 => $notifiable->name, 2 => (string) $this->case->external_case_number, 3 => $summary, 4 => $url],
            fallbackText: "LegalWeb - Caso {$this->case->case_number}. {$summary}. Ver: {$url}",
        );
    }

    private function headline(): string
    {
        if ($this->newActuaciones > 0 || empty($this->cambios['campos'])) {
            return "{$this->newActuaciones} nueva(s) actuacion(es)";
        }

        return count($this->cambios['campos']).' dato(s) del proceso cambiaron';
    }
}
