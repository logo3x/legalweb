<?php

namespace App\Notifications;

use App\Models\LegalCase;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Aviso diario al abogado: procesos que LegalWeb no pudo consultar. Mientras dure el fallo,
 * el abogado debe revisarlos a mano: puede haber actuaciones nuevas que LegalWeb no ve.
 */
class SyncFailuresNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private const MAX_LISTED = 15;

    /**
     * @param  Collection<int, LegalCase>  $cases
     */
    public function __construct(public Collection $cases) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->cases->count();

        $mail = (new MailMessage)
            ->subject($count === 1
                ? "No pudimos revisar el proceso {$this->cases->first()->case_number}"
                : "No pudimos revisar {$count} de sus procesos")
            ->greeting("Hola {$notifiable->name}")
            ->line($count === 1
                ? 'En la última revisión automática no pudimos consultar este proceso. Hasta que se resuelva, revíselo directamente: puede haber actuaciones nuevas que LegalWeb no ve.'
                : "En la última revisión automática no pudimos consultar {$count} procesos. Hasta que se resuelva, revíselos directamente: puede haber actuaciones nuevas que LegalWeb no ve.");

        foreach ($this->cases->take(self::MAX_LISTED) as $case) {
            $since = $case->sync_failed_at?->format('d/m/Y H:i');
            $mail->line("- **{$case->case_number}** · {$case->external_case_number} — {$case->sync_error} (desde {$since})");
        }

        if ($count > self::MAX_LISTED) {
            $mail->line('- y '.($count - self::MAX_LISTED).' proceso(s) más.');
        }

        return $mail
            ->action('Consultar en la Rama Judicial', 'https://consultaprocesos.ramajudicial.gov.co/Procesos/NumeroRadicacion')
            ->line('LegalWeb vuelve a intentarlo cada hora y le avisará de nuevo mañana si el problema continúa.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $count = $this->cases->count();
        $first = $this->cases->first();

        return FilamentNotification::make()
            ->title($count === 1 ? "No pudimos revisar el proceso {$first->case_number}" : "No pudimos revisar {$count} procesos")
            ->body(($first->sync_error ?? 'Sin respuesta').'. Revíselos directamente en la Rama Judicial o SAMAI mientras se resuelve.')
            ->danger()
            ->icon('heroicon-o-exclamation-triangle')
            ->actions([
                Action::make('ver')
                    ->label($count === 1 ? 'Ver el caso' : 'Ver los casos')
                    ->url($count === 1 ? url("/admin/legal-cases/{$first->id}") : url('/admin/legal-cases'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
