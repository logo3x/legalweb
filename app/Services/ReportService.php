<?php

namespace App\Services;

use App\Models\CaseEvent;
use App\Models\CaseFlowProgress;
use App\Models\Client;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;

class ReportService
{
    public function getFirmReport(Firm $firm): array
    {
        $firmFilter = fn ($q) => $q->withoutGlobalScopes()->where('firm_id', $firm->id);

        $totalCases = LegalCase::withoutGlobalScopes()->where('firm_id', $firm->id)->count();
        $totalClients = Client::withoutGlobalScopes()->where('firm_id', $firm->id)->count();
        $totalUsers = User::where('firm_id', $firm->id)->count();

        $casesByStatus = LegalCase::withoutGlobalScopes()
            ->where('firm_id', $firm->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $casesByType = LegalCase::withoutGlobalScopes()
            ->where('firm_id', $firm->id)
            ->join('case_types', 'case_types.id', '=', 'legal_cases.case_type_id')
            ->selectRaw('case_types.name, count(*) as total')
            ->groupBy('case_types.name')
            ->pluck('total', 'name')
            ->toArray();

        $casesByPriority = LegalCase::withoutGlobalScopes()
            ->where('firm_id', $firm->id)
            ->selectRaw('priority, count(*) as total')
            ->groupBy('priority')
            ->pluck('total', 'priority')
            ->toArray();

        $casesByLawyer = LegalCase::withoutGlobalScopes()
            ->where('legal_cases.firm_id', $firm->id)
            ->join('users', 'users.id', '=', 'legal_cases.user_id')
            ->selectRaw('users.name, count(*) as total')
            ->groupBy('users.name')
            ->pluck('total', 'name')
            ->toArray();

        $caseIds = LegalCase::withoutGlobalScopes()->where('firm_id', $firm->id)->pluck('id');

        $recentEvents = CaseEvent::whereIn('legal_case_id', $caseIds)
            ->where('event_date', '>=', now()->subDays(30))
            ->count();

        $completedSteps = CaseFlowProgress::whereIn('legal_case_id', $caseIds)
            ->where('status', 'completado')
            ->count();

        $pendingSteps = CaseFlowProgress::whereIn('legal_case_id', $caseIds)
            ->whereIn('status', ['pendiente', 'en_progreso'])
            ->count();

        $closedCases = $casesByStatus['cerrado'] ?? 0;
        $avgDaysPerCase = 0;

        if ($closedCases > 0) {
            $avgDays = LegalCase::withoutGlobalScopes()
                ->where('firm_id', $firm->id)
                ->where('status', 'cerrado')
                ->whereNotNull('started_at')
                ->whereNotNull('closed_at')
                ->selectRaw('AVG(DATEDIFF(closed_at, started_at)) as avg_days')
                ->value('avg_days');

            $avgDaysPerCase = round($avgDays ?? 0);
        }

        // Analitica de despachos
        $courtStats = LegalCase::withoutGlobalScopes()
            ->where('firm_id', $firm->id)
            ->whereNotNull('court')
            ->where('court', '!=', '')
            ->selectRaw('court, count(*) as total_cases,
                SUM(CASE WHEN status = "cerrado" THEN 1 ELSE 0 END) as cerrados,
                AVG(CASE WHEN status = "cerrado" AND started_at IS NOT NULL AND closed_at IS NOT NULL THEN DATEDIFF(closed_at, started_at) ELSE NULL END) as avg_dias')
            ->groupBy('court')
            ->orderByDesc('total_cases')
            ->limit(10)
            ->get()
            ->map(fn ($c) => [
                'despacho' => $c->court,
                'total_cases' => $c->total_cases,
                'cerrados' => $c->cerrados,
                'activos' => $c->total_cases - $c->cerrados,
                'avg_dias' => $c->avg_dias ? round($c->avg_dias) : null,
            ])
            ->toArray();

        // Actuaciones por tipo (top 10)
        $eventsByType = CaseEvent::whereIn('legal_case_id', $caseIds)
            ->selectRaw('title, count(*) as total')
            ->groupBy('title')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'title')
            ->toArray();

        // Actividad por mes (ultimos 6 meses)
        $monthlyActivity = CaseEvent::whereIn('legal_case_id', $caseIds)
            ->where('event_date', '>=', now()->subMonths(6))
            ->selectRaw('DATE_FORMAT(event_date, "%Y-%m") as mes, count(*) as total')
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes')
            ->toArray();

        return [
            'firm' => $firm,
            'generated_at' => now(),
            'total_cases' => $totalCases,
            'total_clients' => $totalClients,
            'total_users' => $totalUsers,
            'cases_by_status' => $casesByStatus,
            'cases_by_type' => $casesByType,
            'cases_by_priority' => $casesByPriority,
            'cases_by_lawyer' => $casesByLawyer,
            'recent_events' => $recentEvents,
            'completed_steps' => $completedSteps,
            'pending_steps' => $pendingSteps,
            'closed_cases' => $closedCases,
            'avg_days_per_case' => $avgDaysPerCase,
            'status_labels' => [
                'abierto' => 'Abierto',
                'en_progreso' => 'En Progreso',
                'en_espera' => 'En Espera',
                'cerrado' => 'Cerrado',
                'archivado' => 'Archivado',
            ],
            'priority_labels' => [
                'baja' => 'Baja',
                'media' => 'Media',
                'alta' => 'Alta',
                'urgente' => 'Urgente',
            ],
            'court_stats' => $courtStats,
            'events_by_type' => $eventsByType,
            'monthly_activity' => $monthlyActivity,
        ];
    }
}
