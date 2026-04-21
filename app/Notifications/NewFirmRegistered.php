<?php

namespace App\Notifications;

use App\Models\Firm;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewFirmRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Firm $firm,
        public User $owner,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $totalFirms = Firm::count();
        $totalUsers = User::count();

        return (new MailMessage)
            ->subject("Nueva firma registrada: {$this->firm->name}")
            ->greeting('Hola')
            ->line('Se registro una nueva firma en LegalWeb.')
            ->line("**Firma:** {$this->firm->name}")
            ->line("**Propietario:** {$this->owner->name}")
            ->line("**Email:** {$this->owner->email}")
            ->line('**Fecha:** '.now()->format('d/m/Y H:i'))
            ->line('**IP:** '.(request()?->ip() ?? 'desconocida'))
            ->line('---')
            ->line("**Total firmas registradas:** {$totalFirms}")
            ->line("**Total usuarios:** {$totalUsers}")
            ->action('Ver panel admin', url('/admin/firms'))
            ->salutation('LegalWeb - Sistema de notificaciones');
    }
}
