<?php

namespace App\Services;

use App\Models\CaseEvent;
use App\Models\LegalCase;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Estadisticas de tiempos por despacho judicial para una firma.
 */
class CourtAnalyticsService
{
    private const ACTIVE_STATUSES = ['abierto', 'en_progreso', 'en_espera'];

    /**
     * Dias sin actuaciones a partir de los cuales un caso activo se considera estancado.
     */
    public const STALE_DAYS = 60;

    /**
     * @param  array<int, int>|null  $onlyCaseIds  Limita a estos casos (colaboradores que no son administradores).
     * @return array{
     *     courts: array<int, array{court: string, total_cases: int, active_cases: int, total_events: int, avg_days_between_events: float|null, avg_days_since_last_event: float|null, avg_closed_duration_days: float|null, stale_cases: int}>,
     *     totals: array{courts: int, cases: int, events: int, stale_cases: int},
     *     slowest: array<string, mixed>|null,
     *     fastest: array<string, mixed>|null,
     * }
     */
    public function forFirm(int $firmId, ?array $onlyCaseIds = null): array
    {
        $cases = LegalCase::withoutGlobalScopes()
            ->where('firm_id', $firmId)
            ->when($onlyCaseIds !== null, fn ($q) => $q->whereIn('id', $onlyCaseIds))
            ->whereNull('deleted_at')
            ->whereNotNull('court')
            ->where('court', '!=', '')
            ->get(['id', 'court', 'status', 'started_at', 'closed_at']);

        $eventDates = CaseEvent::whereIn('legal_case_id', $cases->pluck('id'))
            ->orderBy('event_date')
            ->get(['legal_case_id', 'event_date'])
            ->groupBy('legal_case_id')
            ->map(fn (Collection $events): Collection => $events->pluck('event_date'));

        $courts = $cases
            ->groupBy(fn (LegalCase $case): string => $this->normalizeCourt($case->court))
            ->map(fn (Collection $courtCases, string $court): array => $this->courtStats($court, $courtCases, $eventDates))
            ->sortByDesc('total_cases')
            ->values();

        $comparable = $courts->filter(fn (array $c): bool => $c['avg_days_between_events'] !== null);

        return [
            'courts' => $courts->all(),
            'totals' => [
                'courts' => $courts->count(),
                'cases' => $cases->count(),
                'events' => $courts->sum('total_events'),
                'stale_cases' => $courts->sum('stale_cases'),
            ],
            'slowest' => $comparable->sortByDesc('avg_days_between_events')->first(),
            'fastest' => $comparable->count() > 1 ? $comparable->sortBy('avg_days_between_events')->first() : null,
        ];
    }

    /**
     * @param  Collection<int, LegalCase>  $cases
     * @param  Collection<int, Collection<int, Carbon>>  $eventDates
     * @return array<string, mixed>
     */
    private function courtStats(string $court, Collection $cases, Collection $eventDates): array
    {
        $gaps = [];
        $sinceLast = [];
        $staleCases = 0;
        $totalEvents = 0;

        foreach ($cases as $case) {
            $dates = $eventDates->get($case->id, collect());
            $totalEvents += $dates->count();

            $previous = null;
            foreach ($dates as $date) {
                if ($previous) {
                    $gaps[] = $previous->diffInDays($date);
                }
                $previous = $date;
            }

            if (in_array($case->status, self::ACTIVE_STATUSES, true) && $previous) {
                $days = (int) $previous->diffInDays(now());
                $sinceLast[] = max(0, $days);

                if ($days >= self::STALE_DAYS) {
                    $staleCases++;
                }
            }
        }

        $closedDurations = $cases
            ->filter(fn (LegalCase $case): bool => $case->started_at && $case->closed_at)
            ->map(fn (LegalCase $case): float => $case->started_at->diffInDays($case->closed_at));

        return [
            'court' => $court,
            'total_cases' => $cases->count(),
            'active_cases' => $cases->whereIn('status', self::ACTIVE_STATUSES)->count(),
            'total_events' => $totalEvents,
            'avg_days_between_events' => $this->average($gaps),
            'avg_days_since_last_event' => $this->average($sinceLast),
            'avg_closed_duration_days' => $this->average($closedDurations->all()),
            'stale_cases' => $staleCases,
        ];
    }

    /**
     * @param  array<int, int|float>  $values
     */
    private function average(array $values): ?float
    {
        return $values === [] ? null : round(array_sum($values) / count($values), 1);
    }

    private function normalizeCourt(string $court): string
    {
        return mb_convert_case(trim(preg_replace('/\s+/', ' ', $court)), MB_CASE_TITLE, 'UTF-8');
    }
}
