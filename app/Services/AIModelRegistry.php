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

    public const REFRESH_STATE_KEY = 'ai.verified_models.refresh_state';

    /**
     * Espera maxima por modelo al buscar modelos nuevos.
     */
    private const PROBE_TIMEOUT = 25;

    /**
     * Prueba con una pregunta real y margen de tokens similar al de la app: algunos modelos
     * gratuitos "razonan" hasta agotar los tokens y nunca entregan respuesta.
     */
    private const PROBE_PROMPT = 'En Colombia, si un juzgado inadmite una demanda civil, indica en una frase el plazo para subsanarla y la norma del Codigo General del Proceso.';

    private const PROBE_MAX_TOKENS = 1000;

    private const PROBE_MIN_ANSWER_LENGTH = 30;

    private const EXCLUDED_PATTERN = '/tts|image|audio|live|embed|safety|guard|code|vision-only/i';

    /**
     * Modelos de NVIDIA que no sirven para redactar texto juridico.
     */
    private const NVIDIA_EXCLUDED_PATTERN = '/reward|parse|retriever|clip|translate|detector|calibration|vlm|-vl-|vision|neva|vila|fuyu|kosmos|deplot|cosmos|omni|starcoder|gemma-2b|recurrentgemma|llama2|sea-lion|palmyra-(med|fin)|nemoguard|topic-control/i';

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
        // Primero los modelos de proveedores que no entrenan con los datos (orden estable).
        $keys = collect(array_keys($this->options()))
            ->sortBy(fn (string $key, int $index): array => [AIProviderClient::mayTrainOnData(AIProviderClient::parseKey($key)[0]) ? 1 : 0, $index])
            ->values()
            ->all();

        // Solo se acepta un modelo de la lista verificada: la clave llega del navegador y
        // un modelo arbitrario podria ser de pago o de un proveedor que entrena con los datos.
        if ($preferredKey && in_array($preferredKey, $keys, true)) {
            array_unshift($keys, $preferredKey);
        }

        return array_values(array_unique($keys));
    }

    /**
     * Opciones del selector de modelos indicando cuales no entrenan con los datos.
     *
     * @return array<string, string>
     */
    public function labeledOptions(): array
    {
        $options = [];

        foreach ($this->attemptOrder() as $key) {
            $options[$key] = $this->options()[$key].(AIProviderClient::mayTrainOnData(AIProviderClient::parseKey($key)[0]) ? '' : ' · no entrena con sus datos');
        }

        return $options;
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
     * Busqueda completa de modelos (consola o pruebas). En la web se usa refreshStep(),
     * porque el hosting corta las peticiones largas.
     *
     * @return list<array{key: string, label: string, verified_at: string}>
     */
    public function refresh(): array
    {
        @set_time_limit(600);

        do {
            $step = $this->refreshStep(null);
        } while (! $step['done'] && ! $step['busy']);

        return $step['verified'];
    }

    public function refreshInProgress(): bool
    {
        return Cache::has(self::REFRESH_STATE_KEY);
    }

    /**
     * Avance de la busqueda en curso.
     *
     * @return array{probed: int, total: int, started_at: string}|null
     */
    public function refreshProgress(): ?array
    {
        $state = Cache::get(self::REFRESH_STATE_KEY);

        if (! is_array($state)) {
            return null;
        }

        return ['probed' => (int) $state['position'], 'total' => count($state['candidates']), 'started_at' => (string) $state['started_at']];
    }

    /**
     * Un tramo de la busqueda de modelos: consulta los catalogos (al empezar), prueba candidatos
     * hasta agotar $budgetSeconds y guarda el avance para continuar en el siguiente tramo
     * (otro clic o el cron de cada minuto). Con $budgetSeconds = null corre hasta terminar.
     *
     * @return array{done: bool, busy: bool, verified: list<array{key: string, label: string, verified_at: string}>, probed: int, total: int}
     */
    public function refreshStep(?int $budgetSeconds): array
    {
        $lock = Cache::lock('ai.verified_models.refresh_lock', ($budgetSeconds ?? 600) + 60);

        if (! $lock->get()) {
            $progress = $this->refreshProgress();

            return ['done' => false, 'busy' => true, 'verified' => [], 'probed' => $progress['probed'] ?? 0, 'total' => $progress['total'] ?? 0];
        }

        try {
            $startedAt = microtime(true);
            $state = Cache::get(self::REFRESH_STATE_KEY);

            // Una busqueda que quedo a medias hace mas de 6 horas se descarta y se empieza de nuevo.
            if (! is_array($state) || now()->subHours(6)->gt($state['started_at'])) {
                $state = [
                    'candidates' => $this->discoverCandidates(),
                    'position' => 0,
                    'verified' => [],
                    'per_provider' => [],
                    'probed' => [],
                    'previous' => $this->verifiedModels(),
                    'started_at' => now()->toDateTimeString(),
                ];
                Cache::forever(self::REFRESH_STATE_KEY, $state);
            }

            $keys = array_keys($state['candidates']);
            $previous = collect($state['previous'])->keyBy('key');
            $probedThisStep = 0;

            while ($state['position'] < count($keys)) {
                // Siempre se prueba al menos un modelo por tramo para que la busqueda avance.
                if ($budgetSeconds !== null && $probedThisStep > 0 && (microtime(true) - $startedAt) + self::PROBE_TIMEOUT > $budgetSeconds) {
                    break;
                }

                $key = $keys[$state['position']];
                $label = $state['candidates'][$key];
                $state['position']++;
                [$provider] = AIProviderClient::parseKey($key);

                // Se prueban candidatos hasta reunir max_candidates que respondan por proveedor
                // (con data_collection=deny muchos gratuitos se descartan).
                if (($state['per_provider'][$provider] ?? 0) >= $this->maxCandidates($provider)) {
                    continue;
                }

                $probedThisStep++;
                $state['probed'][] = $key;
                $response = $this->client->complete($key, 'Eres un abogado colombiano.', self::PROBE_PROMPT, self::PROBE_MAX_TOKENS, self::PROBE_TIMEOUT);

                if (mb_strlen(trim((string) $response['content'])) >= self::PROBE_MIN_ANSWER_LENGTH) {
                    $state['verified'][] = ['key' => $key, 'label' => $label, 'verified_at' => now()->toDateTimeString()];
                    $state['per_provider'][$provider] = ($state['per_provider'][$provider] ?? 0) + 1;
                } elseif ($previous->has($key) && $this->isTransientFailure($response['status'])) {
                    // Fallo transitorio (saturacion, limite de uso): se conserva la verificacion anterior.
                    $state['verified'][] = $previous->get($key);
                    $state['per_provider'][$provider] = ($state['per_provider'][$provider] ?? 0) + 1;
                }

                // Avance guardado tras cada prueba: si el hosting corta la ejecucion, no se pierde lo verificado
                // y se conservan los modelos anteriores que aun no se alcanzaron a probar.
                if ($state['verified']) {
                    Cache::forever(self::CACHE_KEY, array_values(array_merge(
                        $state['verified'],
                        $previous->except($state['probed'])->all(),
                    )));
                }

                Cache::forever(self::REFRESH_STATE_KEY, $state);
            }

            if ($state['position'] < count($keys)) {
                return ['done' => false, 'busy' => false, 'verified' => $state['verified'], 'probed' => $state['position'], 'total' => count($keys)];
            }

            Cache::forget(self::REFRESH_STATE_KEY);
            Cache::forever(self::CACHE_KEY.'.refreshed_at', now()->toDateTimeString());

            if ($state['verified']) {
                Cache::forever(self::CACHE_KEY, $state['verified']);
            } else {
                Cache::forever(self::CACHE_KEY, $state['previous']);
                Log::error('AI: ningun modelo respondio durante la verificacion; se conserva la lista anterior');
            }

            return ['done' => true, 'busy' => false, 'verified' => $state['verified'], 'probed' => count($keys), 'total' => count($keys)];
        } finally {
            $lock->release();
        }
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
        $byProvider = [
            AIProviderClient::OPENROUTER => fn (): array => $this->discoverOpenRouterModels(),
            AIProviderClient::GEMINI => fn (): array => $this->discoverGeminiModels(),
            AIProviderClient::NVIDIA => fn (): array => $this->discoverNvidiaModels(),
        ];

        $preferred = config('services.ai.preferred_provider');

        if (isset($byProvider[$preferred])) {
            $byProvider = [$preferred => $byProvider[$preferred]] + $byProvider;
        }

        $lists = array_values(array_map(fn (callable $discover): array => $discover(), $byProvider));
        $keys = array_map('array_keys', $lists);
        $candidates = [];

        for ($i = 0; $i < max(array_map('count', $keys)); $i++) {
            foreach (array_keys($lists) as $list) {
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
            $response = Http::timeout(15)
                ->withHeaders(['x-goog-api-key' => (string) config('services.gemini.api_key')])
                ->get(config('services.gemini.base_url').'/models', ['pageSize' => 200]);

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
            Log::info('Gemini models list error: '.AIProviderClient::redactSecrets($e->getMessage()));
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
     * Modelos de chat del catalogo de NVIDIA. Muchos aparecen en la lista pero la cuenta
     * no los tiene habilitados (404): la verificacion los descarta.
     *
     * @return array<string, string>
     */
    private function discoverNvidiaModels(): array
    {
        if (! $this->client->isConfigured(AIProviderClient::NVIDIA)) {
            return [];
        }

        $catalog = [];

        try {
            $response = Http::timeout(15)
                ->withToken((string) config('services.nvidia.api_key'))
                ->get(config('services.nvidia.base_url').'/models');

            foreach ($response->json('data', []) as $model) {
                $id = $model['id'] ?? '';

                if ($id === '' || preg_match(self::EXCLUDED_PATTERN, $id) || preg_match(self::NVIDIA_EXCLUDED_PATTERN, $id)) {
                    continue;
                }

                $catalog[$id] = $id;
            }
        } catch (\Exception $e) {
            Log::info('NVIDIA models list error: '.$e->getMessage());
        }

        // Primero el configurado y los grandes de NVIDIA (mejor calidad en espanol juridico).
        $preferred = array_filter([config('services.nvidia.model'), 'nvidia/nemotron-3-ultra-550b-a55b', 'nvidia/nemotron-3-super-120b-a12b', 'nvidia/llama-3.1-nemotron-ultra-253b-v1']);
        $ordered = [];

        foreach ($preferred as $id) {
            if (! $catalog || isset($catalog[$id])) {
                $ordered[$id] = $id;
            }
        }

        $ordered += $catalog;

        return collect(array_slice($ordered, 0, $this->probeBudget(AIProviderClient::NVIDIA), true))
            ->mapWithKeys(fn (string $id) => [AIProviderClient::makeKey(AIProviderClient::NVIDIA, $id) => self::nvidiaLabel($id)])
            ->all();
    }

    private static function nvidiaLabel(string $id): string
    {
        $name = str($id)->afterLast('/')->replace('-', ' ')->title()->toString();

        return "{$name} (NVIDIA)";
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
            AIProviderClient::makeKey(AIProviderClient::NVIDIA, (string) config('services.nvidia.model')),
        ])
            ->filter(fn (string $key) => filled(AIProviderClient::parseKey($key)[1]))
            ->unique()
            ->map(fn (string $key) => ['key' => $key, 'label' => AIProviderClient::parseKey($key)[1], 'verified_at' => ''])
            ->values()
            ->all();
    }
}
