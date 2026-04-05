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
        $cases = LegalCase::where('firm_id', $firm->id)->where('is_demo', false);
        $clients = Client::where('firm_id', $firm->id)->where('is_demo', false);
        $users = User::where('firm_id', $firm->id);

        $totalCases = $cases->count();
        $casesByStatus = LegalCase::where('firm_id', $firm->id)
            ->where('is_demo', false)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $casesByType = LegalCase::where('firm_id', $firm->id)
            ->where('is_demo', false)
            ->join('case_types', 'case_types.id', '=', 'legal_cases.case_type_id')
            ->selectRaw('case_types.name, count(*) as total')
            ->groupBy('case_types.name')
            ->pluck('total', 'name')
            ->toArray();

        $casesByPriority = LegalCase::where('firm_id', $firm->id)
            ->where('is_demo', false)
            ->selectRaw('priority, count(*) as total')
            ->groupBy('priority')
            ->pluck('total', 'priority')
            ->toArray();

        $casesByLawyer = LegalCase::where('legal_cases.firm_id', $firm->id)
            ->where('is_demo', false)
            ->join('users', 'users.id', '=', 'legal_cases.user_id')
            ->selectRaw('users.name, count(*) as total')
            ->groupBy('users.name')
            ->pluck('total', 'name')
            ->toArray();

        $recentEvents = CaseEvent::whereHas('legalCase', fn ($q) => $q->where('firm_id', $firm->id)->where('is_demo', false))
            ->where('event_date', '>=', now()->subDays(30))
            ->count();

        $completedSteps = CaseFlowProgress::whereHas('legalCase', fn ($q) => $q->where('firm_id', $firm->id)->where('is_demo', false))
            ->where('status', 'completado')
            ->count();

        $pendingSteps = CaseFlowProgress::whereHas('legalCase', fn ($q) => $q->where('firm_id', $firm->id)->where('is_demo', false))
            ->whereIn('status', ['pendiente', 'en_progreso'])
            ->count();

        $closedCases = $casesByStatus['cerrado'] ?? 0;
        $avgDaysPerCase = 0;

        if ($closedCases > 0) {
            $avgDays = LegalCase::where('firm_id', $firm->id)
                ->where('is_demo', false)
                ->where('status', 'cerrado')
                ->whereNotNull('started_at')
                ->whereNotNull('closed_at')
                ->selectRaw('AVG(DATEDIFF(closed_at, started_at)) as avg_days')
                ->value('avg_days');

            $avgDaysPerCase = round($avgDays ?? 0);
        }

        return [
            'firm' => $firm,
            'generated_at' => now(),
            'total_cases' => $totalCases,
            'total_clients' => $clients->count(),
            'total_users' => $users->count(),
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
        ];
    }
}
