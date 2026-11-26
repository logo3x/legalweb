<?php

namespace App\Services;

use App\Models\CaseEvent;
use App\Models\CaseFlowProgress;
use App\Models\LegalCase;
use App\Models\Reminder;
use Illuminate\Support\Carbon;

/**
 * Lo pendiente de un caso, para mostrarlo arriba en la ficha: el proximo termino que corre,
 * la proxima cita de la agenda y la ultima actuacion conocida.
 */
class CasePendingSummary
{
    public function __construct(private JudicialCalendarService $calendar) {}

    /**
     * Termino que vence primero entre las etapas iniciadas del flujo procesal.
     *
     * @return array{step: string, deadline: Carbon, remaining: int}|null
     */
    public function nextDeadline(LegalCase $case): ?array
    {
        return CaseFlowProgress::with('flowStep')
            ->where('legal_case_id', $case->getKey())
            ->where('status', 'en_progreso')
            ->whereNotNull('started_at')
            ->whereHas('flowStep', fn ($query) => $query->where('days_limit', '>', 0))
            ->get()
            ->map(function (CaseFlowProgress $progress): array {
                $deadline = $this->calendar->calculateDeadline($progress->started_at, (int) $progress->flowStep->days_limit)['deadline'];

                return [
                    'step' => (string) $progress->flowStep->name,
                    'deadline' => $deadline,
                    'remaining' => $this->calendar->businessDaysBetween(now(), $deadline),
                ];
            })
            ->sortBy(fn (array $item): int => $item['deadline']->getTimestamp())
            ->first();
    }

    public function nextReminder(LegalCase $case): ?Reminder
    {
        return Reminder::where('legal_case_id', $case->getKey())
            ->where('is_completed', false)
            ->where('due_date', '>=', now()->startOfDay())
            ->orderBy('due_date')
            ->first();
    }

    public function lastEvent(LegalCase $case): ?CaseEvent
    {
        return CaseEvent::where('legal_case_id', $case->getKey())
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->first();
    }
}
