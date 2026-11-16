<?php

namespace App\Notifications;

use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Novedades del sistema para el superadmin: cambios en los modelos de IA, fuentes judiciales
 * que cambian su servicio, etc. Llega por correo y queda en la campana del panel.
 */
class SystemNewsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $lines
     */
    public function __construct(
        public string $title,
        public array $lines,
        public string $actionLabel = 'Abrir el panel',
        public string $actionUrl = '',
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
        $mail = (new MailMessage)
            ->subject('Novedades de LegalWeb: '.$this->title)
            ->greeting("Hola {$notifiable->name}")
            ->line('La revisión automática de LegalWeb detectó lo siguiente:');

        foreach ($this->lines as $line) {
            $mail->line('- '.$line);
        }

        return $mail
            ->action($this->actionLabel, $this->actionUrl ?: url('/admin'))
            ->line('No necesita hacer nada si el cambio es el esperado: LegalWeb ya usa la mejor opción disponible.')
            ->salutation('Atentamente,'."\n".'LegalWeb');
    }

    /**
     * Formato de las notificaciones de la campana de Filament.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->title($this->title)
            ->body(implode(' ', $this->lines))
            ->info()
            ->icon('heroicon-o-megaphone')
            ->actions([
                Action::make('abrir')
                    ->label($this->actionLabel)
                    ->url($this->actionUrl ?: url('/admin'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
