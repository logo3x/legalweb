<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Descubre, verifica y recuerda los modelos de IA que estan respondiendo.
 *
 * Los proveedores (especialmente OpenRouter) retiran modelos con frecuencia,
 * asi que en lugar de una lista fija se consulta el catalogo, se prueba cada
 * candidato con una solicitud minima y se guardan los que respondieron.
 */
class AIModelRegistry
{
    public const CACHE_KEY = 'ai.verified_models';

    /**
     * Prueba con una pregunta real y margen de tokens similar al de la app: algunos modelos
     * gratuitos "razonan" hasta agotar los tokens y nunca entregan respuesta.
     */
    private const PROBE_PROMPT = 'En Colombia, si un juzgado inadmite una demanda civil, indica en una frase el plazo para subsanarla y la norma del Codigo General del Proceso.';

    private const PROBE_MAX_TOKENS = 1000;

    private const PROBE_MIN_ANSWER_LENGTH = 30;

    private const EXCLUDED_PATTERN = '/tts|image|audio|live|embed|safety|guard|code|vision-only/i';

    public function __construct(private AIProviderClient $client) {}

    /**
     * @return list<array{key: string, label: string, verified_at: string}>
     */
    public function verifiedModels(): array
    {
        return Cache::get(self::CACHE_KEY, []);
    }

    public function lastVerifiedAt(): ?string
    {
        return Cache::get(self::CACHE_KEY.'.refreshed_at');
    }

    /**
     * Opciones para el select de modelos (clave => etiqueta).
     *
     * @return array<string, string>
     */
    public function options(): array
    {
        $options = collect($this->verifiedModels() ?: $this->fallbackModels())
            ->mapWithKeys(fn (array $model) => [$model['key'] => $model['label']])
            ->all();

        return array_filter(
            $options,
            fn (string $key) => $this->client->isConfigured(AIProviderClient::parseKey($key)[0]),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Orden en que se intentaran los modelos, con el preferido primero.
     *
     * @return list<string>
     */
    public function attemptOrder(?string $preferredKey = null): array
    {
        $keys = array_keys($this->options());

        if ($preferredKey) {
            array_unshift($keys, $preferredKey);
        }

        return array_values(array_unique($keys));
    }

    public function labelFor(string $key): string
    {
        return $this->options()[$key] ?? AIProviderClient::parseKey($key)[1];
    }

    /**
     * Registra el resultado de una llamada real para mantener la lista al dia.
     */
    public function recordResult(string $key, bool $succeeded, ?int $status = null, ?string $label = null): void
    {
        $models = $this->verifiedModels();
        $exists = in_array($key, array_column($models, 'key'), true);

        if ($succeeded && ! $exists) {
            $models[] = ['key' => $key, 'label' => $label ?? $this->labelFor($key), 'verified_at' => now()->toDateTimeString()];
            Cache::forever(self::CACHE_KEY, $models);
        }

        if (! $succeeded && $exists && ! $this->isTransientFailure($status)) {
            Cache::forever(self::CACHE_KEY, array_values(array_filter($models, fn (array $model) => $model['key'] !== $key)));
            Log::warning("AI: modelo {$key} retirado de la lista de verificados (HTTP {$status})");
        }
    }

    /**
     * Candidatos que todavia no se han probado, para recuperarse cuando todos los verificados fallan.
     *
     * @param  list<string>  $exclude
     * @return array<string, string>
     */
    public function untriedCandidates(array $exclude, int $limit): array
    {
        return array_slice(array_diff_key($this->discoverCandidates(), array_flip($exclude)), 0, $limit, true);
    }

    /**
     * Consulta el catalogo de cada proveedor, prueba los candidatos y guarda los que responden.
     *
     * @return list<array{key: string, label: string, verified_at: string}>
     */
    public function refresh(): array
    {
        // En hosting compartido PHP suele cortar a los 30-60 s; se pide mas tiempo si se permite.
        @set_time_limit(300);

        $verified = [];
        $verifiedPerProvider = [];
        $previous = collect($this->verifiedModels())->keyBy('key');
        $probed = [];

        foreach ($this->discoverCandidates() as $key => $label) {
            [$provider] = AIProviderClient::parseKey($key);

            // Se prueban candidatos hasta reunir max_candidates que respondan por proveedor
            // (con data_collection=deny muchos gratuitos se descartan).
            if (($verifiedPerProvider[$provider] ?? 0) >= $this->maxCandidates($provider)) {
                continue;
            }

            $probed[] = $key;
            $response = $this->client->complete($key, 'Eres un abogado colombiano.', self::PROBE_PROMPT, self::PROBE_MAX_TOKENS, 25);

            if (mb_strlen(trim((string) $response['content'])) >= self::PROBE_MIN_ANSWER_LENGTH) {
                $verified[] = ['key' => $key, 'label' => $label, 'verified_at' => now()->toDateTimeString()];
                $verifiedPerProvider[$provider] = ($verifiedPerProvider[$provider] ?? 0) + 1;
            } elseif ($previous->has($key) && $this->isTransientFailure($response['status'])) {
                // Fallo transitorio (saturacion, limite de uso): se conserva la verificacion anterior.
                $verified[] = $previous->get($key);
                $verifiedPerProvider[$provider] = ($verifiedPerProvider[$provider] ?? 0) + 1;
            }

            // Avance guardado tras cada prueba: si el hosting corta la ejecucion, no se pierde lo verificado
            // y se conservan los modelos anteriores que aun no se alcanzaron a probar.
            if ($verified) {
                Cache::forever(self::CACHE_KEY, array_values(array_merge(
                    $verified,
                    $previous->except($probed)->all(),
                )));
            }
        }

        if ($verified) {
            Cache::forever(self::CACHE_KEY, $verified);
            Cache::forever(self::CACHE_KEY.'.refreshed_at', now()->toDateTimeString());
        } else {
            Log::error('AI: ningun modelo respondio durante la verificacion; se conserva la lista anterior');
        }

        return $verified;
    }

    /**
     * @return array<string, string>
     */
    public function discoverCandidates(): array
    {
        // Se alternan proveedores para que ninguno quede sin probar si la ejecucion se corta.
        // El preferido va primero y por eso tambien encabeza el orden automatico de uso:
        // OpenRouter con data_collection=deny no entrena con los datos; Gemini gratuito si puede,
        // pero suele responder mejor en derecho colombiano (AI_PREFERRED_PROVIDER).
        $lists = config('services.ai.preferred_provider') === AIProviderClient::GEMINI
            ? [$this->discoverGeminiModels(), $this->discoverOpenRouterModels()]
            : [$this->discoverOpenRouterModels(), $this->discoverGeminiModels()];

        $candidates = [];
        $keys = array_map('array_keys', $lists);

        for ($i = 0; $i < max(count($keys[0]), count($keys[1])); $i++) {
            foreach ([0, 1] as $list) {
                if (isset($keys[$list][$i])) {
                    $candidates[$keys[$list][$i]] = $lists[$list][$keys[$list][$i]];
                }
            }
        }

        return $candidates;
    }

    /**
     * Puede volver a funcionar: sin respuesta HTTP, clave/credito de la cuenta (401/402), timeout,
     * saturacion o limite de uso (429) y errores del proveedor (5xx).
     * No es transitorio: un 200 sin respuesta (agoto los tokens razonando), 400, 403, 404 o 410.
     */
    private function isTransientFailure(?int $status): bool
    {
        return $status === null || in_array($status, [401, 402, 408, 429], true) || $status >= 500;
    }

    private function maxCandidates(string $provider): int
    {
        return (int) config("services.{$provider}.max_candidates", $provider === AIProviderClient::GEMINI ? 3 : 5);
    }

    /**
     * Cuantos candidatos se consultan como maximo por proveedor (el triple de los que se guardan).
     */
    private function probeBudget(string $provider): int
    {
        return $this->maxCandidates($provider) * 3;
    }

    /**
     * @return array<string, string>
     */
    private function discoverGeminiModels(): array
    {
        if (! $this->client->isConfigured(AIProviderClient::GEMINI)) {
            return [];
        }

        $preferred = array_filter([config('services.gemini.model'), 'gemini-flash-latest', 'gemini-flash-lite-latest']);
        $catalog = [];

        try {
            $response = Http::timeout(15)->get(config('services.gemini.base_url').'/models', [
                'key' => config('services.gemini.api_key'),
                'pageSize' => 200,
            ]);

            foreach ($response->json('models', []) as $model) {
                $id = str_replace('models/', '', $model['name'] ?? '');

                if (! in_array('generateContent', $model['supportedGenerationMethods'] ?? [], true)
                    || ! str_contains($id, 'flash')
                    || preg_match(self::EXCLUDED_PATTERN, $id)
                    || str_contains($id, 'preview')) {
                    continue;
                }

                $catalog[$id] = $model['displayName'] ?? $id;
            }
        } catch (\Exception $e) {
            Log::info('Gemini models list error: '.$e->getMessage());
        }

        // Los alias "-latest" siempre apuntan al modelo vigente, por eso van primero.
        $ordered = [];

        foreach ($preferred as $id) {
            if (! $catalog || isset($catalog[$id])) {
                $ordered[$id] = $catalog[$id] ?? $id;
            }
        }

        $ordered += array_reverse($catalog, true);

        return collect(array_slice($ordered, 0, $this->probeBudget(AIProviderClient::GEMINI), true))
            ->mapWithKeys(fn (string $label, string $id) => [AIProviderClient::makeKey(AIProviderClient::GEMINI, $id) => "{$label} (Gemini)"])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function discoverOpenRouterModels(): array
    {
        if (! $this->client->isConfigured(AIProviderClient::OPENROUTER)) {
            return [];
        }

        $catalog = [];

        try {
            $response = Http::timeout(15)->get(config('services.openrouter.base_url').'/models');

            foreach ($response->json('data', []) as $model) {
                $id = $model['id'] ?? '';
                // Solo variantes ":free" oficiales; los modelos "stealth" gratuitos registran los prompts.
                if (! str_ends_with($id, ':free')
                    || str_starts_with($id, 'stealth/')
                    || ! in_array('text', $model['architecture']['output_modalities'] ?? ['text'], true)
                    || ($model['context_length'] ?? 0) < 32000
                    || preg_match(self::EXCLUDED_PATTERN, $id)) {
                    continue;
                }

                $catalog[$id] = $model['name'] ?? $id;
            }
        } catch (\Exception $e) {
            Log::info('OpenRouter models list error: '.$e->getMessage());
        }

        $configured = config('services.openrouter.model');

        if ($configured && (! $catalog || isset($catalog[$configured]))) {
            $catalog = [$configured => $catalog[$configured] ?? $configured] + $catalog;
        }

        return collect(array_slice($catalog, 0, $this->probeBudget(AIProviderClient::OPENROUTER), true))
            ->mapWithKeys(fn (string $label, string $id) => [AIProviderClient::makeKey(AIProviderClient::OPENROUTER, $id) => "{$label} (OpenRouter)"])
            ->all();
    }

    /**
     * Lista minima cuando aun no se ha corrido ninguna verificacion.
     *
     * @return list<array{key: string, label: string, verified_at: string}>
     */
    private function fallbackModels(): array
    {
        return collect([
            AIProviderClient::makeKey(AIProviderClient::GEMINI, config('services.gemini.model')),
            AIProviderClient::makeKey(AIProviderClient::GEMINI, 'gemini-flash-latest'),
            AIProviderClient::makeKey(AIProviderClient::OPENROUTER, (string) config('services.openrouter.model')),
        ])
            ->filter(fn (string $key) => filled(AIProviderClient::parseKey($key)[1]))
            ->unique()
            ->map(fn (string $key) => ['key' => $key, 'label' => AIProviderClient::parseKey($key)[1], 'verified_at' => ''])
            ->values()
            ->all();
    }
}
