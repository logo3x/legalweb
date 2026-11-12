<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Transporte HTTP hacia los proveedores de IA (Gemini directo y OpenRouter).
 *
 * Los modelos se identifican con una clave "proveedor:modelo",
 * por ejemplo "gemini:gemini-flash-latest" u "openrouter:google/gemma-4-31b-it:free".
 *
 * @phpstan-type CompletionResult array{content: ?string, status: ?int, error: ?string, usage: array{prompt_tokens: int, completion_tokens: int, total_tokens: int}}
 */
class AIProviderClient
{
    public const GEMINI = 'gemini';

    public const OPENROUTER = 'openrouter';

    public const NVIDIA = 'nvidia';

    /**
     * Nombre visible de cada proveedor.
     *
     * @var array<string, string>
     */
    public const PROVIDER_LABELS = [
        self::GEMINI => 'Gemini',
        self::OPENROUTER => 'OpenRouter',
        self::NVIDIA => 'NVIDIA',
    ];

    /**
     * @return array{0: string, 1: string}
     */
    public static function parseKey(string $key): array
    {
        [$provider, $model] = array_pad(explode(':', $key, 2), 2, '');

        return [$provider, $model];
    }

    public static function makeKey(string $provider, string $model): string
    {
        return "{$provider}:{$model}";
    }

    /**
     * Si el proveedor puede usar lo que se le envia para entrenar o mejorar sus modelos.
     *
     * - Gemini gratuito: si (condiciones de los servicios no pagos de Google).
     * - NVIDIA (prueba): no esta claro en sus condiciones; se trata como que si.
     * - OpenRouter: no, solo con data_collection=deny (enruta a proveedores que no guardan ni entrenan).
     */
    public static function mayTrainOnData(string $provider): bool
    {
        return match ($provider) {
            self::OPENROUTER => config('services.openrouter.data_collection') !== 'deny',
            default => true,
        };
    }

    public function isConfigured(string $provider): bool
    {
        return filled(config("services.{$provider}.api_key"));
    }

    /**
     * Envia una solicitud de chat al modelo indicado.
     *
     * @return CompletionResult
     */
    public function complete(string $key, string $systemPrompt, string $userMessage, int $maxTokens, int $timeout = 60): array
    {
        [$provider, $model] = self::parseKey($key);

        if (! $model || ! $this->isConfigured($provider)) {
            return $this->result(null, null, "{$key}: proveedor sin API key configurada");
        }

        try {
            return match ($provider) {
                self::GEMINI => $this->completeWithGemini($model, $systemPrompt, $userMessage, $maxTokens, $timeout),
                self::OPENROUTER => $this->completeWithOpenRouter($model, $systemPrompt, $userMessage, $maxTokens, $timeout),
                self::NVIDIA => $this->completeWithNvidia($model, $systemPrompt, $userMessage, $maxTokens, $timeout),
                default => $this->result(null, null, "{$key}: proveedor desconocido"),
            };
        } catch (\Exception $e) {
            Log::info("AI {$key} excepcion: ".$e->getMessage());

            return $this->result(null, null, "{$key}: ".$e->getMessage());
        }
    }

    /**
     * @return CompletionResult
     */
    private function completeWithGemini(string $model, string $systemPrompt, string $userMessage, int $maxTokens, int $timeout): array
    {
        $apiKey = config('services.gemini.api_key');
        $baseUrl = config('services.gemini.base_url');

        $response = Http::timeout($timeout)->post("{$baseUrl}/models/{$model}:generateContent?key={$apiKey}", [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                ['parts' => [['text' => $userMessage]]],
            ],
            'generationConfig' => [
                // Los modelos recientes "piensan" y consumen tokens de salida antes de responder.
                'maxOutputTokens' => $maxTokens + 2048,
                'temperature' => 0.3,
            ],
        ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? 'sin detalle';
            Log::info("Gemini {$model} fallo", ['status' => $response->status(), 'error' => $error]);

            return $this->result(null, $response->status(), "Gemini {$model}: HTTP {$response->status()} - {$error}");
        }

        return $this->result($response->json('candidates.0.content.parts.0.text'), $response->status(), null, [
            'prompt_tokens' => (int) $response->json('usageMetadata.promptTokenCount', 0),
            'completion_tokens' => (int) $response->json('usageMetadata.candidatesTokenCount', 0),
            'total_tokens' => (int) $response->json('usageMetadata.totalTokenCount', 0),
        ]);
    }

    /**
     * @return CompletionResult
     */
    private function completeWithOpenRouter(string $model, string $systemPrompt, string $userMessage, int $maxTokens, int $timeout): array
    {
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.3,
        ];

        if (config('services.openrouter.data_collection') === 'deny') {
            $payload['provider'] = ['data_collection' => 'deny'];
        }

        $response = Http::timeout($timeout)->withHeaders([
            'Authorization' => 'Bearer '.config('services.openrouter.api_key'),
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'LegalWeb',
        ])->post(config('services.openrouter.base_url').'/chat/completions', $payload);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? 'sin detalle';
            Log::info("OpenRouter {$model} fallo", ['status' => $response->status(), 'error' => $error]);

            return $this->result(null, $response->status(), "OpenRouter {$model}: HTTP {$response->status()} - {$error}");
        }

        return $this->result($response->json('choices.0.message.content'), $response->status(), null, [
            'prompt_tokens' => (int) $response->json('usage.prompt_tokens', 0),
            'completion_tokens' => (int) $response->json('usage.completion_tokens', 0),
            'total_tokens' => (int) $response->json('usage.total_tokens', 0),
        ]);
    }

    /**
     * NVIDIA API Catalog: misma forma que la API de chat de OpenAI.
     *
     * @return CompletionResult
     */
    private function completeWithNvidia(string $model, string $systemPrompt, string $userMessage, int $maxTokens, int $timeout): array
    {
        $response = Http::timeout($timeout)
            ->withToken((string) config('services.nvidia.api_key'))
            ->post(config('services.nvidia.base_url').'/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => 0.3,
            ]);

        if (! $response->successful()) {
            $error = $response->json('detail') ?? $response->json('error.message') ?? 'sin detalle';
            Log::info("NVIDIA {$model} fallo", ['status' => $response->status(), 'error' => $error]);

            return $this->result(null, $response->status(), "NVIDIA {$model}: HTTP {$response->status()} - {$error}");
        }

        return $this->result($response->json('choices.0.message.content'), $response->status(), null, [
            'prompt_tokens' => (int) $response->json('usage.prompt_tokens', 0),
            'completion_tokens' => (int) $response->json('usage.completion_tokens', 0),
            'total_tokens' => (int) $response->json('usage.total_tokens', 0),
        ]);
    }

    /**
     * @param  array{prompt_tokens: int, completion_tokens: int, total_tokens: int}|null  $usage
     * @return CompletionResult
     */
    private function result(?string $content, ?int $status, ?string $error, ?array $usage = null): array
    {
        return [
            'content' => $content,
            'status' => $status,
            'error' => $error,
            'usage' => $usage ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0],
        ];
    }
}
