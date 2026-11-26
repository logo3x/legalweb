<?php

namespace App\Notifications;

use App\Models\Firm;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * El cobro automatico de la renovacion con la tarjeta registrada no se pudo hacer.
 */
class AutoRenewFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Firm $firm,
        public bool $willRetry,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $card = trim(($this->firm->card_brand ?? 'Tarjeta').' terminada en '.($this->firm->card_last_four ?? '----'));

        return (new MailMessage)
            ->subject('No pudimos cobrar la renovación de su plan en LegalWeb')
            ->greeting("Hola {$notifiable->name}")
            ->line("Intentamos cobrar la renovación de su plan a la {$card} y el banco no la aprobó.")
            ->line($this->willRetry
                ? 'Volveremos a intentarlo mañana. Si prefiere, puede pagar ahora con otro medio o registrar otra tarjeta.'
                : 'Ya no haremos más intentos automáticos. Para que su cuenta siga activa, pague la renovación con otro medio o registre otra tarjeta.')
            ->action('Ir a Mi Plan', url('/admin/planes'))
            ->line('Su información y sus casos están seguros mientras tanto.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('No pudimos cobrar la renovación con su tarjeta')
            ->body($this->willRetry ? 'Lo intentaremos de nuevo mañana.' : 'Pague la renovación con otro medio o registre otra tarjeta.')
            ->danger()
            ->icon('heroicon-o-credit-card')
            ->actions([
                Action::make('plan')->label('Ir a Mi Plan')->url(url('/admin/planes'))->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
