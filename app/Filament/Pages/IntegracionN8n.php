<?php

namespace App\Filament\Pages;

use App\Jobs\SendWebhookDelivery;
use App\Models\WebhookDelivery;
use App\Services\N8nWebhook;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use UnitEnum;

class IntegracionN8n extends Page
{
    protected string $view = 'filament.pages.integracion-n8n';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = 'Super Admin';

    protected static ?string $navigationLabel = 'Integración n8n';

    protected static ?string $title = 'Integracion con n8n';

    protected static ?string $slug = 'integracion-n8n';

    protected static ?int $navigationSort = 70;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'superadmin';
    }

    public function isReady(): bool
    {
        return Schema::hasTable('webhook_deliveries');
    }

    /**
     * @return array<string, bool>
     */
    public function getStatus(): array
    {
        return [
            'webhook_url' => filled(config('services.n8n.webhook_url')),
            'webhook_secret' => filled(config('services.n8n.webhook_secret')),
            'api_token' => strlen((string) config('services.n8n.api_token')) >= 20,
        ];
    }

    /**
     * @return array<string, array{label: string, enabled: bool}>
     */
    public function getEvents(): array
    {
        $service = app(N8nWebhook::class);

        return collect(N8nWebhook::EVENTS)
            ->except('test.ping')
            ->map(fn (string $label, string $event): array => ['label' => $label, 'enabled' => $service->isEnabled($event)])
            ->all();
    }

    /**
     * @return Collection<int, WebhookDelivery>
     */
    public function getDeliveries(): Collection
    {
        if (! $this->isReady()) {
            return collect();
        }

        return WebhookDelivery::with('firm')->latest('id')->limit(50)->get();
    }

    public function retry(int $deliveryId): void
    {
        abort_unless(static::canAccess(), 403);

        $delivery = WebhookDelivery::find($deliveryId);

        if (! $delivery) {
            return;
        }

        $delivery->update(['status' => WebhookDelivery::STATUS_PENDING]);
        $this->sendNow($delivery);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send_test')
                ->label('Enviar evento de prueba')
                ->icon('heroicon-o-paper-airplane')
                ->disabled(fn (): bool => ! $this->isReady() || ! app(N8nWebhook::class)->isConfigured())
                ->authorize(fn (): bool => static::canAccess())
                ->action(function (): void {
                    $delivery = app(N8nWebhook::class)->dispatch('test.ping', [
                        'message' => 'Prueba de conexion desde LegalWeb',
                        'sent_by' => auth()->user()?->email,
                    ]);

                    if (! $delivery) {
                        Notification::make()->title('No se pudo registrar el evento')->danger()->send();

                        return;
                    }

                    $this->sendNow($delivery);
                }),
        ];
    }

    /**
     * Envia de inmediato (sin esperar al cron de la cola) y muestra el resultado.
     */
    private function sendNow(WebhookDelivery $delivery): void
    {
        try {
            (new SendWebhookDelivery($delivery->id))->handle();
        } catch (\Throwable) {
            // El error ya quedo guardado en la entrega.
        }

        $delivery->refresh();
        $sent = $delivery->status === WebhookDelivery::STATUS_SENT;

        Notification::make()
            ->title($sent ? 'n8n recibio el evento' : 'n8n no recibio el evento')
            ->body($sent ? "Respuesta HTTP {$delivery->response_code}." : ($delivery->error ?: 'Revise la URL del webhook.'))
            ->status($sent ? 'success' : 'danger')
            ->send();
    }
}
