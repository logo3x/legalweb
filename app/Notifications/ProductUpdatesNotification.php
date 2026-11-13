<?php

namespace App\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Anuncio de novedades dentro del panel (campana de notificaciones).
 */
class ProductUpdatesNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public string $actionLabel,
        public string $actionUrl,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->title)
            ->body($this->body)
            ->icon('heroicon-o-sparkles')
            ->iconColor('info')
            ->actions([
                Action::make('ver')
                    ->label($this->actionLabel)
                    ->url($this->actionUrl)
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
