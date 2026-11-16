<?php

namespace App\Services;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Estado de salud de cada modelo de IA: si responde, cuanto tarda y su ultimo error.
 *
 * Se alimenta de dos fuentes: las llamadas reales de los usuarios (gratis) y
 * pruebas cortas automaticas o manuales. Las pruebas automaticas solo se hacen
 * a modelos sin noticias recientes, para no gastar la cuota gratuita de los proveedores.
 *
 * @phpstan-type HealthEntry array{key: string, label: string, ok: bool, status: ?int, error: ?string, latency_ms: ?int, checked_at: string, source: string, failures: int}
 */
class AIModelHealth
{
    public const CACHE_KEY = 'ai.model_health';

    public const ALERT_KEY = 'ai.model_health.alerted';

    public const REFRESH_LOCK_KEY = 'ai.model_health.refresh_lock';

    private const PROBE_PROMPT = 'Responde en una sola frase corta: que es una accion de tutela en Colombia.';

    private const PROBE_MAX_TOKENS = 600;

    private const PROBE_TIMEOUT = 25;

    public function __construct(
        private AIProviderClient $client,
        private AIModelRegistry $registry,
    ) {}

    /**
     * @return array<string, HealthEntry>
     */
    public function all(): array
    {
        return Cache::get(self::CACHE_KEY, []);
    }

    /**
     * Guarda el resultado de una llamada (real o de prueba) a un modelo.
     */
    public function record(string $key, bool $ok, ?int $status, ?string $error, ?int $latencyMs, string $source, ?string $label = null): void
    {
        $entries = $this->all();
        $previous = $entries[$key] ?? null;

        $entries[$key] = [
            'key' => $key,
            'label' => $label ?? $previous['label'] ?? $this->registry->labelFor($key),
            'ok' => $ok,
            'status' => $status,
            'error' => $ok ? null : mb_substr((string) ($error ?: 'Respuesta vacia'), 0, 300),
            'latency_ms' => $latencyMs,
            'checked_at' => now()->toDateTimeString(),
            'source' => $source,
            'failures' => $ok ? 0 : (int) ($previous['failures'] ?? 0) + 1,
        ];

        Cache::forever(self::CACHE_KEY, $entries);
    }

    /**
     * Envia una pregunta corta al modelo y registra si respondio.
     *
     * @return HealthEntry
     */
    public function probe(string $key, string $source = 'prueba'): array
    {
        $start = microtime(true);
        $response = $this->client->complete($key, 'Eres un abogado colombiano.', self::PROBE_PROMPT, self::PROBE_MAX_TOKENS, self::PROBE_TIMEOUT);
        $latency = (int) round((microtime(true) - $start) * 1000);
        $ok = mb_strlen(trim((string) $response['content'])) >= 10;

        $this->registry->recordResult($key, $ok, $response['status']);
        $this->record($key, $ok, $response['status'], $response['error'] ?? null, $latency, $source);

        return $this->all()[$key];
    }

    /**
     * Prueba todos los modelos disponibles.
     *
     * @return list<HealthEntry>
     */
    public function probeAll(?int $budgetSeconds = null): array
    {
        @set_time_limit(300);
        $startedAt = microtime(true);
        $results = [];

        foreach (array_keys($this->registry->options()) as $key) {
            // Con tiempo limitado se detiene antes de que el hosting corte la peticion.
            if ($budgetSeconds !== null && $results !== [] && (microtime(true) - $startedAt) + self::PROBE_TIMEOUT > $budgetSeconds) {
                break;
            }

            $results[] = $this->probe($key);
        }

        return $results;
    }

    /**
     * Filas para la pantalla de Uso de IA, en el orden en que la app intenta los modelos.
     *
     * @return list<array{key: string, label: string, state: string, status: ?int, error: ?string, latency_ms: ?int, checked_at: ?Carbon, source: ?string, failures: int, position: int, trains: bool}>
     */
    public function report(): array
    {
        $health = $this->all();
        $rows = [];
        $position = 1;

        $options = $this->registry->options();

        foreach ($this->registry->attemptOrder() as $key) {
            $rows[] = $this->row($key, $options[$key], $health[$key] ?? null, $position++, false);
        }

        // Modelos que fallaron de forma definitiva y salieron de la lista: se muestran para tener contexto.
        foreach ($health as $key => $entry) {
            if (! isset($this->registry->options()[$key]) && Carbon::parse($entry['checked_at'])->gt(now()->subDays(3))) {
                $rows[] = $this->row($key, $entry['label'], $entry, 0, true);
            }
        }

        return $rows;
    }

    /**
     * Modelos disponibles que respondieron en su ultima comprobacion.
     */
    public function healthyCount(): int
    {
        $health = $this->all();

        return collect(array_keys($this->registry->options()))
            ->filter(fn (string $key) => ($health[$key]['ok'] ?? null) === true)
            ->count();
    }

    /**
     * Comprobacion automatica (la llama el cron cada pocos minutos).
     * Solo prueba los modelos sin noticias en las ultimas horas, de a pocos.
     *
     * @return list<string> claves probadas
     */
    public function runScheduledCheck(int $maxProbes = 2): array
    {
        $health = $this->all();
        $staleBefore = now()->subHours($this->intervalHours());

        $due = collect(array_keys($this->registry->options()))
            ->filter(function (string $key) use ($health, $staleBefore) {
                $entry = $health[$key] ?? null;

                // Un modelo que fallo se vuelve a probar en una hora, sin esperar el intervalo completo.
                $threshold = ($entry['ok'] ?? true) ? $staleBefore : now()->subHour();

                return ! $entry || Carbon::parse($entry['checked_at'])->lt($threshold);
            })
            ->sortBy(fn (string $key) => $health[$key]['checked_at'] ?? '')
            ->take($maxProbes)
            ->values()
            ->all();

        foreach ($due as $key) {
            $this->probe($key, 'automatica');
        }

        $this->handleOutage();
        $this->notifyChanges();

        return $due;
    }

    /**
     * Correo de novedades al superadmin si cambio el modelo principal u otra cosa relevante.
     * Nunca interrumpe la comprobacion automatica.
     */
    public function notifyChanges(): void
    {
        try {
            app(AIChangeNotifier::class)->check();
        } catch (\Throwable $e) {
            Log::warning('AI: no se pudieron revisar las novedades de los modelos', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Si ningun modelo responde: busca modelos nuevos y avisa al superadmin (como maximo cada 6 horas).
     */
    private function handleOutage(): void
    {
        $configured = array_filter(array_keys(AIProviderClient::PROVIDER_LABELS), fn (string $provider): bool => $this->client->isConfigured($provider));

        if (! $configured) {
            return;
        }

        // Los modelos que fallan de forma definitiva salen de la lista; si quedan otros sin comprobar, aun no es caida total.
        $health = $this->all();
        $allChecked = collect(array_keys($this->registry->options()))->every(fn (string $key) => isset($health[$key]));

        if ($this->healthyCount() > 0 || ! $allChecked) {
            return;
        }

        if (Cache::add(self::REFRESH_LOCK_KEY, true, now()->addHours(6))) {
            Log::warning('AI: ningun modelo responde; se buscan modelos nuevos');
            // Primer tramo corto: el resto lo continua el cron de cada minuto (el hosting corta peticiones largas).
            $this->registry->refreshStep(30);
        }

        if ($this->healthyCount() === 0 && Cache::add(self::ALERT_KEY, true, now()->addHours(6))) {
            $superadmins = User::where('role', 'superadmin')->get();

            if ($superadmins->isNotEmpty()) {
                Notification::make()
                    ->title('La IA no esta respondiendo')
                    ->body('Ninguno de los modelos de IA respondió en la última comprobación automática. En Uso de IA puede ver el error de cada modelo, probarlos de nuevo o buscar modelos nuevos.')
                    ->danger()
                    ->actions([
                        Action::make('abrir_uso_ia')
                            ->label('Abrir Uso de IA')
                            ->button()
                            ->url(url('/admin/uso-ia'))
                            ->markAsRead(),
                    ])
                    ->sendToDatabase($superadmins);
            }
        }
    }

    private function intervalHours(): int
    {
        return max(1, (int) config('services.ai.health_interval_hours', 6));
    }

    /**
     * @param  HealthEntry|null  $entry
     * @return array{key: string, label: string, state: string, status: ?int, error: ?string, latency_ms: ?int, checked_at: ?Carbon, source: ?string, failures: int, position: int, trains: bool}
     */
    private function row(string $key, string $label, ?array $entry, int $position, bool $retired): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'state' => match (true) {
                $retired => 'retirado',
                $entry === null => 'sin_datos',
                $entry['ok'] => 'activo',
                default => 'falla',
            },
            'status' => $entry['status'] ?? null,
            'error' => $entry['error'] ?? null,
            'latency_ms' => $entry['latency_ms'] ?? null,
            'checked_at' => isset($entry['checked_at']) ? Carbon::parse($entry['checked_at']) : null,
            'source' => $entry['source'] ?? null,
            'failures' => (int) ($entry['failures'] ?? 0),
            'position' => $position,
            'trains' => AIProviderClient::mayTrainOnData(AIProviderClient::parseKey($key)[0]),
        ];
    }
}
