<?php

namespace App\Filament\Widgets;

use App\Models\CaseEvent;
use App\Models\Client;
use App\Models\LegalCase;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CasesOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $firmId = auth()->user()->firm_id;

        $totalCases = LegalCase::where('firm_id', $firmId)->count();
        $activeCases = LegalCase::where('firm_id', $firmId)->whereIn('status', ['abierto', 'en_progreso'])->count();
        $totalClients = Client::where('firm_id', $firmId)->count();
        $recentEvents = CaseEvent::whereHas('legalCase', fn ($q) => $q->where('firm_id', $firmId))
            ->where('event_date', '>=', now()->subDays(30))
            ->count();

        $firm = auth()->user()->firm;
        $casesRemaining = $firm ? $firm->casesRemaining() : 0;

        return [
            Stat::make('Casos Activos', $activeCases)
                ->description("{$totalCases} casos en total")
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary'),
            Stat::make('Clientes', $totalClients)
                ->description('Registrados en la firma')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            Stat::make('Actuaciones (30 dias)', $recentEvents)
                ->description('Ultimos 30 dias')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning'),
            Stat::make('Casos Disponibles', $casesRemaining)
                ->description('Segun su plan actual')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
