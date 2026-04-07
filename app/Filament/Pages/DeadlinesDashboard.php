<?php

namespace App\Filament\Pages;

use App\Models\CaseEvent;
use App\Models\LegalCase;
use App\Models\Reminder;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class DeadlinesDashboard extends Page
{
    protected string $view = 'filament.pages.deadlines-dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Vencimientos';

    protected static ?string $title = 'Dashboard de Vencimientos';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    public function getDeadlinesData(): array
    {
        $userId = auth()->id();
        $firmId = auth()->user()->firm_id;

        // Vencimientos proximos (recordatorios pendientes)
        $reminders = Reminder::with('legalCase')
            ->where('user_id', $userId)
            ->where('is_completed', false)
            ->where('due_date', '>=', now()->subDays(7))
            ->where('due_date', '<=', now()->addDays(30))
            ->orderBy('due_date')
            ->get()
            ->map(function ($r) {
                $days = (int) now()->startOfDay()->diffInDays($r->due_date->startOfDay(), false);

                return [
                    'id' => $r->id,
                    'title' => $r->title,
                    'description' => $r->description,
                    'due_date' => $r->due_date->format('d/m/Y'),
                    'due_date_raw' => $r->due_date->format('Y-m-d'),
                    'days' => $days,
                    'type' => $r->type,
                    'priority' => $r->priority,
                    'case_number' => $r->legalCase?->case_number,
                    'case_id' => $r->legal_case_id,
                    'status' => match (true) {
                        $days < 0 => 'vencido',
                        $days === 0 => 'hoy',
                        $days <= 3 => 'urgente',
                        $days <= 7 => 'proximo',
                        default => 'normal',
                    },
                ];
            });

        // Ultimas actuaciones de Rama Judicial (ultimos 15 dias)
        $recentActuaciones = CaseEvent::with('legalCase')
            ->whereHas('legalCase', fn ($q) => $q->where('firm_id', $firmId))
            ->where('event_type', 'actuacion')
            ->where('event_date', '>=', now()->subDays(15))
            ->orderByDesc('event_date')
            ->limit(15)
            ->get()
            ->map(fn ($e) => [
                'title' => $e->title,
                'date' => $e->event_date->format('d/m/Y'),
                'case_number' => $e->legalCase?->case_number,
                'case_id' => $e->legalCase?->id,
            ]);

        // Casos sin sincronizar (mas de 48h)
        $casesStale = LegalCase::where('firm_id', $firmId)
            ->whereNotNull('external_case_number')
            ->where('external_case_number', '!=', '')
            ->whereIn('status', ['abierto', 'en_progreso', 'en_espera'])
            ->where(function ($q) {
                $q->whereNull('last_tyba_sync')
                    ->orWhere('last_tyba_sync', '<', now()->subHours(48));
            })
            ->count();

        // Resumen
        $vencidos = $reminders->where('status', 'vencido')->count();
        $hoy = $reminders->where('status', 'hoy')->count();
        $urgentes = $reminders->where('status', 'urgente')->count();
        $proximos = $reminders->where('status', 'proximo')->count();

        return [
            'reminders' => $reminders,
            'recentActuaciones' => $recentActuaciones,
            'casesStale' => $casesStale,
            'summary' => [
                'vencidos' => $vencidos,
                'hoy' => $hoy,
                'urgentes' => $urgentes,
                'proximos' => $proximos,
                'total' => $reminders->count(),
            ],
        ];
    }
}
