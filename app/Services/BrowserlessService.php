<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrowserlessService
{
    private string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.browserless.api_key', '');
        $this->baseUrl = 'https://production-sfo.browserless.io';
    }

    /**
     * Obtener el HTML renderizado de una URL usando un navegador real.
     */
    public function getRenderedHtml(string $url, int $waitMs = 3000): ?string
    {
        if (! $this->apiKey) {
            Log::error('Browserless: API key no configurada');

            return null;
        }

        $response = Http::timeout(60)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->baseUrl}/content?token={$this->apiKey}", [
                'url' => $url,
                'waitForTimeout' => $waitMs,
                'gotoOptions' => [
                    'waitUntil' => 'networkidle2',
                ],
            ]);

        if (! $response->successful()) {
            Log::error('Browserless: error', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            return null;
        }

        return $response->body();
    }
}
