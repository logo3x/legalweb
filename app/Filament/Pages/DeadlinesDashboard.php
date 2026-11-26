<?php

namespace App\Filament\Pages;

use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\LegalCase;
use App\Services\CasePendingSummary;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Vencimientos: los terminos de las etapas procesales y los recordatorios de vencimiento,
 * agrupados por urgencia. Las audiencias, reuniones y tareas viven en la Agenda.
 */
class DeadlinesDashboard extends Page
{
    protected string $view = 'filament.pages.deadlines-dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Vencimientos';

    protected static ?string $slug = 'vencimientos';

    protected static ?string $title = 'Vencimientos';

    protected static ?int $navigationSort = 3;

    public function getSubheading(): ?string
    {
        return 'Términos de las etapas de sus procesos y recordatorios de vencimiento, contados en días hábiles. Las audiencias y reuniones están en la Agenda.';
    }

    /**
     * @return array{groups: list<array{key: string, title: string, items: list<array<string, mixed>>}>, total: int, unmonitored: int, unmonitoredUrl: string}
     */
    public function getDeadlinesData(): array
    {
        $user = auth()->user();
        $items = app(CasePendingSummary::class)->deadlinesFor($user);

        $groups = [
            ['key' => 'overdue', 'title' => 'Vencidos', 'items' => $items->filter(fn (array $item): bool => $item['remaining'] < 0)],
            ['key' => 'today', 'title' => 'Vencen hoy', 'items' => $items->filter(fn (array $item): bool => $item['remaining'] === 0)],
            ['key' => 'soon', 'title' => 'Próximos 3 días hábiles', 'items' => $items->filter(fn (array $item): bool => $item['remaining'] >= 1 && $item['remaining'] <= 3)],
            ['key' => 'later', 'title' => 'Más adelante (30 días)', 'items' => $items->filter(fn (array $item): bool => $item['remaining'] > 3)],
        ];

        // Procesos que no se pudieron consultar o llevan mas de 48 horas sin consulta: sus
        // terminos pueden no estar al dia, y el silencio no significa "sin novedades".
        $unmonitored = LegalCase::where('firm_id', $user->firm_id)
            ->where('is_demo', false)
            ->whereNotNull('external_case_number')
            ->where('external_case_number', '!=', '')
            ->whereIn('status', ['abierto', 'en_progreso', 'en_espera'])
            ->where(fn ($query) => $query->whereNotNull('sync_failed_at')
                ->orWhereNull('last_tyba_sync')
                ->orWhere('last_tyba_sync', '<', now()->subHours(48)))
            ->count();

        return [
            'groups' => array_map(fn (array $group): array => [...$group, 'items' => $group['items']->values()->all()], $groups),
            'total' => $items->count(),
            'unmonitored' => $unmonitored,
            'unmonitoredUrl' => LegalCaseResource::getUrl('index', ['tableFilters' => ['sync_failed' => ['isActive' => true]]]),
        ];
    }
}
