<?php

namespace App\Notifications;

use App\Models\Client;
use App\Notifications\Concerns\SendsWhatsApp;
use App\Notifications\Messages\WhatsAppMessage;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * La vigilancia por documento encontro procesos del cliente que la firma aun no ha importado.
 */
class NewProcessesFoundNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsWhatsApp;

    /**
     * @param  array<int, array<string, mixed>>  $processes
     */
    public function __construct(
        public Client $client,
        public array $processes,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->withWhatsApp(['mail', 'database'], $notifiable);
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(count($this->processes).' proceso(s) nuevo(s) de '.$this->client->full_name)
            ->body('La vigilancia por documento encontro procesos que aun no estan en LegalWeb.')
            ->icon('heroicon-o-shield-exclamation')
            ->iconColor('warning')
            ->actions([
                Action::make('ver')
                    ->label('Revisar e importar')
                    ->url($this->searchUrl())
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Procesos nuevos de su cliente '.$this->client->full_name)
            ->greeting("Dr(a). {$notifiable->name}")
            ->line('La vigilancia de LegalWeb encontro en la Rama Judicial **'.count($this->processes).' proceso(s)** donde aparece su cliente **'.$this->client->full_name.'** y que aun no estan en su cuenta:');

        foreach (array_slice($this->processes, 0, 8) as $process) {
            $mail->line('- '.$process['radicado'].' · '.($process['despacho'] ?? '').(! empty($process['fecha']) ? ' (radicado el '.$process['fecha'].')' : ''));
        }

        if (count($this->processes) > 8) {
            $mail->line('- y '.(count($this->processes) - 8).' mas.');
        }

        return $mail
            ->line('Revise si corresponden a su cliente antes de importarlos: la busqueda se hace por numero de documento en la consulta publica de la Rama Judicial.')
            ->action('Revisar e importar', $this->searchUrl())
            ->salutation('LegalWeb - Control inteligente de sus procesos legales')
            ->with($this->client->user?->firm?->emailBrand() ?? []);
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $count = (string) count($this->processes);

        return new WhatsAppMessage(
            template: 'client.new_processes.lawyer',
            variables: [1 => $notifiable->name, 2 => $this->client->full_name, 3 => $count, 4 => $this->searchUrl()],
            fallbackText: "LegalWeb - Encontramos {$count} proceso(s) nuevo(s) de su cliente {$this->client->full_name}. Revise: {$this->searchUrl()}",
        );
    }

    private function searchUrl(): string
    {
        return url('/admin/buscar-procesos-tyba?'.http_build_query([
            'documento' => $this->client->document_number,
            'tipo_documento' => $this->client->ramaDocumentTypeCode(),
        ]));
    }
}
