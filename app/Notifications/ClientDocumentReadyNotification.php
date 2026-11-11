<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\LegalCase;
use App\Notifications\Concerns\SendsWhatsApp;
use App\Notifications\Messages\WhatsAppMessage;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClientDocumentReadyNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsWhatsApp;

    public function __construct(
        public LegalCase $case,
        public Document $document,
        public ?string $action = 'ready',
    ) {}

    public function via(object $notifiable): array
    {
        return $this->withWhatsApp(['mail', 'database'], $notifiable);
    }

    public function toDatabase(object $notifiable): array
    {
        $title = $this->action === 'uploaded'
            ? "Cliente subio documento: {$this->document->name}"
            : "Cliente tiene listo documento: {$this->document->name}";

        $color = $this->action === 'uploaded' ? 'success' : 'info';

        return FilamentNotification::make()
            ->title($title)
            ->body("Caso {$this->case->case_number} - {$this->case->client->full_name}")
            ->icon('heroicon-o-document-check')
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
        $client = $this->case->client;
        $subject = $this->action === 'uploaded'
            ? "Cliente subio documento - {$this->case->case_number}"
            : "Cliente tiene listo documento - {$this->case->case_number}";

        $line1 = $this->action === 'uploaded'
            ? "El cliente **{$client->full_name}** subio el siguiente documento:"
            : "El cliente **{$client->full_name}** indica que tiene listo el siguiente documento:";

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Dr(a). {$notifiable->name}")
            ->line($line1)
            ->line("**{$this->document->name}**")
            ->line("Caso: {$this->case->title} ({$this->case->case_number})")
            ->action('Ver caso', url("/admin/legal-cases/{$this->case->id}"))
            ->salutation('LegalWeb - Control inteligente de sus procesos legales')
            ->with($this->case->user?->firm?->emailBrand() ?? []);
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $url = url("/admin/legal-cases/{$this->case->id}");
        $client = $this->case->client->full_name;
        $verb = $this->action === 'uploaded' ? 'envio el enlace de' : 'tiene listo';

        return new WhatsAppMessage(
            template: 'document.received.lawyer',
            variables: [1 => $notifiable->name, 2 => $client, 3 => $this->document->name, 4 => $url],
            fallbackText: "LegalWeb - Su cliente {$client} {$verb} '{$this->document->name}' (caso {$this->case->case_number}). Ver: {$url}",
        );
    }
}
