<?php

namespace App\Filament\Pages;

use App\Models\AiUsageLog;
use App\Services\AIModelHealth;
use App\Services\AIModelRegistry;
use App\Services\AIProviderClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use UnitEnum;

class UsoIa extends Page
{
    protected string $view = 'filament.pages.uso-ia';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Super Admin';

    protected static ?string $navigationLabel = 'Uso de IA';

    protected static ?string $title = 'Uso de IA por firma';

    protected static ?string $slug = 'uso-ia';

    protected static ?int $navigationSort = 60;

    public ?string $rangeKey = '30d';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'superadmin';
    }

    /**
     * Estado de cada modelo de IA, en el orden en que la app los intenta.
     *
     * @return list<array<string, mixed>>
     */
    public function getModelHealth(): array
    {
        return app(AIModelHealth::class)->report();
    }

    /**
     * OpenRouter configurado pero sin data_collection=deny: puede enrutar a proveedores que entrenan.
     */
    public function openRouterAllowsTraining(): bool
    {
        return filled(config('services.openrouter.api_key')) && AIProviderClient::mayTrainOnData(AIProviderClient::OPENROUTER);
    }

    public function getLastModelsRefresh(): ?string
    {
        return app(AIModelRegistry::class)->lastVerifiedAt();
    }

    /**
     * Prueba un modelo puntual desde el boton de su fila.
     */
    public function probeModel(string $key): void
    {
        abort_unless(static::canAccess(), 403);

        if (! array_key_exists($key, app(AIModelRegistry::class)->options()) || ! $this->allowProbe()) {
            return;
        }

        $entry = app(AIModelHealth::class)->probe($key);
        $notification = Notification::make()->title($entry['label']);

        if ($entry['ok']) {
            $notification->body('Respondio en '.number_format((int) $entry['latency_ms']).' ms.')->success()->send();
        } else {
            $notification->body('No respondio: '.($entry['error'] ?? 'sin detalle'))->danger()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('probe_all')
                ->label('Probar todos los modelos')
                ->icon('heroicon-o-signal')
                ->authorize(fn (): bool => static::canAccess())
                ->action(function (): void {
                    if (! $this->allowProbe()) {
                        return;
                    }

                    $results = app(AIModelHealth::class)->probeAll();
                    $ok = count(array_filter($results, fn (array $entry): bool => $entry['ok']));

                    $notification = Notification::make()->title("{$ok} de ".count($results).' modelo(s) respondieron');
                    $ok > 0 ? $notification->success()->send() : $notification->danger()->send();
                }),
            Action::make('refresh_models')
                ->label('Buscar modelos nuevos')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Consulta los catalogos de Gemini, OpenRouter y NVIDIA y prueba los modelos gratuitos. Puede tardar varios minutos.')
                ->authorize(fn (): bool => static::canAccess())
                ->action(function (): void {
                    if (! $this->allowProbe()) {
                        return;
                    }

                    $verified = app(AIModelRegistry::class)->refresh();

                    $notification = Notification::make()->title(count($verified).' modelo(s) disponibles');
                    $verified ? $notification->success()->send() : $notification->danger()->send();
                }),
        ];
    }

    /**
     * Limita las pruebas manuales para no agotar la cuota gratuita de los proveedores.
     */
    private function allowProbe(): bool
    {
        $allowed = RateLimiter::attempt('ai-probe:'.auth()->id(), 10, fn (): bool => true, 3600);

        if (! $allowed) {
            Notification::make()->title('Demasiadas pruebas')->body('Espere un rato antes de volver a probar los modelos.')->warning()->send();
        }

        return (bool) $allowed;
    }

    public function setRange(string $key): void
    {
        $this->rangeKey = in_array($key, ['7d', '30d', '90d', 'all']) ? $key : '30d';
    }

    public function getRangeStart(): ?Carbon
    {
        return match ($this->rangeKey) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => null,
        };
    }

    public function isReady(): bool
    {
        return Schema::hasTable('ai_usage_logs');
    }

    private function baseQuery()
    {
        $q = AiUsageLog::query();
        $start = $this->getRangeStart();
        if ($start) {
            $q->where('created_at', '>=', $start);
        }

        return $q;
    }

    public function getKpis(): array
    {
        if (! $this->isReady()) {
            return ['total_calls' => 0, 'total_tokens' => 0, 'failed_calls' => 0, 'active_firms' => 0, 'avg_latency_ms' => 0];
        }
        $base = $this->baseQuery();

        return [
            'total_calls' => (int) (clone $base)->count(),
            'total_tokens' => (int) (clone $base)->sum('total_tokens'),
            'failed_calls' => (int) (clone $base)->where('success', false)->count(),
            'active_firms' => (int) (clone $base)->whereNotNull('firm_id')->distinct('firm_id')->count('firm_id'),
            'avg_latency_ms' => (int) round((float) (clone $base)->where('success', true)->avg('latency_ms') ?? 0),
        ];
    }

    public function getByFirm(): array
    {
        if (! $this->isReady()) {
            return [];
        }

        return $this->baseQuery()
            ->whereNotNull('firm_id')
            ->select(
                'firm_id',
                DB::raw('COUNT(*) as calls'),
                DB::raw('SUM(total_tokens) as tokens'),
                DB::raw('SUM(prompt_tokens) as prompt_tokens'),
                DB::raw('SUM(completion_tokens) as completion_tokens'),
                DB::raw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as fails'),
                DB::raw('MAX(created_at) as last_used_at')
            )
            ->groupBy('firm_id')
            ->orderByDesc('tokens')
            ->with('firm:id,name')
            ->limit(50)
            ->get()
            ->map(fn ($r) => [
                'firm_id' => $r->firm_id,
                'firm_name' => $r->firm?->name ?? 'Sin firma',
                'calls' => (int) $r->calls,
                'tokens' => (int) $r->tokens,
                'prompt_tokens' => (int) $r->prompt_tokens,
                'completion_tokens' => (int) $r->completion_tokens,
                'fails' => (int) $r->fails,
                'last_used_at' => $r->last_used_at ? Carbon::parse($r->last_used_at) : null,
            ])
            ->toArray();
    }

    public function getByAction(): array
    {
        if (! $this->isReady()) {
            return [];
        }

        return $this->baseQuery()
            ->select('action', DB::raw('COUNT(*) as calls'), DB::raw('SUM(total_tokens) as tokens'))
            ->groupBy('action')
            ->orderByDesc('tokens')
            ->get()
            ->map(fn ($r) => [
                'action' => $r->action,
                'label' => AiUsageLog::ACTIONS[$r->action] ?? $r->action,
                'calls' => (int) $r->calls,
                'tokens' => (int) $r->tokens,
            ])
            ->toArray();
    }

    public function getByProvider(): array
    {
        if (! $this->isReady()) {
            return [];
        }

        return $this->baseQuery()
            ->where('success', true)
            ->select('provider', DB::raw('COUNT(*) as calls'), DB::raw('SUM(total_tokens) as tokens'))
            ->groupBy('provider')
            ->orderByDesc('tokens')
            ->get()
            ->map(fn ($r) => [
                'provider' => $r->provider,
                'calls' => (int) $r->calls,
                'tokens' => (int) $r->tokens,
            ])
            ->toArray();
    }

    public function getDailySeries(): array
    {
        if (! $this->isReady()) {
            return [];
        }
        $start = $this->getRangeStart() ?? now()->subDays(30);

        return AiUsageLog::query()
            ->where('created_at', '>=', $start)
            ->select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('SUM(total_tokens) as tokens'),
                DB::raw('COUNT(*) as calls')
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($r) => [
                'day' => $r->day,
                'tokens' => (int) $r->tokens,
                'calls' => (int) $r->calls,
            ])
            ->toArray();
    }
}
