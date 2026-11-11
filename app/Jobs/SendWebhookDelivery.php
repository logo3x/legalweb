<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\N8nWebhook;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SendWebhookDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(public int $deliveryId) {}

    /**
     * Segundos de espera entre reintentos: 1 min, 5 min, 15 min, 1 h.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function handle(): void
    {
        $delivery = WebhookDelivery::find($this->deliveryId);
        $url = (string) config('services.n8n.webhook_url');

        if (! $delivery || $delivery->status === WebhookDelivery::STATUS_SENT || $url === '') {
            return;
        }

        $body = json_encode($delivery->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $headers = [
            'X-LegalWeb-Event' => $delivery->event,
            'X-LegalWeb-Delivery' => $delivery->uuid,
        ];

        $secret = (string) config('services.n8n.webhook_secret');
        if ($secret !== '') {
            $headers['X-LegalWeb-Signature'] = N8nWebhook::sign($body, $secret);
        }

        $delivery->increment('attempts');

        try {
            $response = Http::withHeaders($headers)
                ->withBody($body, 'application/json')
                ->timeout(15)
                ->post($url);
        } catch (\Throwable $e) {
            $this->markFailed($delivery, null, 'Sin conexion: '.$e->getMessage());

            throw new RuntimeException('n8n no respondio: '.$e->getMessage(), 0, $e);
        }

        if ($response->successful()) {
            $delivery->update([
                'status' => WebhookDelivery::STATUS_SENT,
                'response_code' => $response->status(),
                'error' => null,
                'sent_at' => now(),
            ]);

            return;
        }

        $this->markFailed($delivery, $response->status(), mb_substr($response->body(), 0, 300));

        // Los 4xx (salvo 408/429) no mejoran reintentando: URL o flujo mal configurado.
        if ($response->clientError() && ! in_array($response->status(), [408, 429], true)) {
            $this->fail(new RuntimeException("n8n rechazo el evento (HTTP {$response->status()})"));

            return;
        }

        throw new RuntimeException("n8n respondio HTTP {$response->status()}");
    }

    private function markFailed(WebhookDelivery $delivery, ?int $status, string $error): void
    {
        $delivery->update([
            'status' => WebhookDelivery::STATUS_FAILED,
            'response_code' => $status,
            'error' => mb_substr($error, 0, 500),
        ]);
    }
}
