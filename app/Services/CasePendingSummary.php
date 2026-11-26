<?php

namespace App\Services;

use App\Filament\Resources\CaseEvents\CaseEventResource;
use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\CaseEvent;
use App\Models\CaseFlowProgress;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Lo pendiente de los casos: el proximo termino que corre, la proxima cita de la agenda y
 * la ultima actuacion conocida. Tambien arma la lista de la pagina Vencimientos.
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
        return $this->runningSteps()
            ->where('legal_case_id', $case->getKey())
            ->get()
            ->map(fn (CaseFlowProgress $progress): array => [
                'step' => (string) $progress->flowStep->name,
                ...$this->stepDeadline($progress),
            ])
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

    /**
     * Vencimientos del usuario: terminos de las etapas que corren en sus casos y sus
     * recordatorios de tipo vencimiento. Incluye los vencidos que siguen sin cerrar.
     *
     * @return Collection<int, array{kind: string, title: string, detail: ?string, case_number: ?string, case_url: ?string, date: Carbon, remaining: int}>
     */
    public function deadlinesFor(User $user, int $days = 30): Collection
    {
        $until = now()->addDays($days)->endOfDay();
        $caseIds = CaseEventResource::caseIdsWithPermission('flow.view');

        $steps = $caseIds === []
            ? collect()
            : $this->runningSteps()
                ->with('legalCase')
                ->whereHas('legalCase', function ($query) use ($user, $caseIds): void {
                    $query->where('firm_id', $user->firm_id)
                        ->whereIn('status', ['abierto', 'en_progreso', 'en_espera'])
                        ->when($caseIds !== null, fn ($q) => $q->whereIn('id', $caseIds));
                })
                ->get()
                ->map(function (CaseFlowProgress $progress): array {
                    $deadline = $this->stepDeadline($progress);

                    return [
                        'kind' => 'termino',
                        'title' => (string) $progress->flowStep->name,
                        'detail' => $progress->legalCase->title,
                        'case_number' => $progress->legalCase->case_number,
                        'case_url' => LegalCaseResource::getUrl('view', ['record' => $progress->legalCase]),
                        'date' => $deadline['deadline'],
                        'remaining' => $deadline['remaining'],
                    ];
                })
                ->filter(fn (array $item): bool => $item['date']->lte($until));

        $reminders = Reminder::with('legalCase')
            ->where('user_id', $user->id)
            ->where('is_completed', false)
            ->where('type', 'vencimiento')
            ->where('due_date', '>=', now()->subDays(7)->startOfDay())
            ->where('due_date', '<=', $until)
            ->get()
            ->map(fn (Reminder $reminder): array => [
                'kind' => 'vencimiento',
                'title' => (string) $reminder->title,
                'detail' => $reminder->description,
                'case_number' => $reminder->legalCase?->case_number,
                'case_url' => $reminder->legalCase ? LegalCaseResource::getUrl('view', ['record' => $reminder->legalCase]) : null,
                'date' => $reminder->due_date,
                'remaining' => $this->calendar->businessDaysBetween(now(), $reminder->due_date),
            ]);

        return $steps->concat($reminders)
            ->sortBy(fn (array $item): int => $item['date']->getTimestamp())
            ->values();
    }

    /**
     * Etapas iniciadas con plazo: el plazo corre en dias habiles desde que se inicio la etapa.
     */
    private function runningSteps()
    {
        return CaseFlowProgress::with('flowStep')
            ->where('status', 'en_progreso')
            ->whereNotNull('started_at')
            ->whereHas('flowStep', fn ($query) => $query->where('days_limit', '>', 0));
    }

    /**
     * @return array{deadline: Carbon, remaining: int}
     */
    private function stepDeadline(CaseFlowProgress $progress): array
    {
        $deadline = $this->calendar->calculateDeadline($progress->started_at, (int) $progress->flowStep->days_limit)['deadline'];

        return [
            'deadline' => $deadline,
            'remaining' => $this->calendar->businessDaysBetween(now(), $deadline),
        ];
    }
}
