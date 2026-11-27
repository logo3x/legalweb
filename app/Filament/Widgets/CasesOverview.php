<?php

namespace App\Filament\Widgets;

use App\Models\CaseEvent;
use App\Models\Client;
use App\Models\LegalCase;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CasesOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /**
     * Cifras de toda la firma: solo para administradores. Lo urgente del dia esta en "Mi dia".
     */
    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    protected function getStats(): array
    {
        $firmId = auth()->user()->firm_id;

        $totalCases = LegalCase::where('firm_id', $firmId)->count();
        $activeCases = LegalCase::where('firm_id', $firmId)->whereIn('status', ['abierto', 'en_progreso'])->count();
        $totalClients = Client::where('firm_id', $firmId)->count();
        $recentEvents = CaseEvent::whereHas('legalCase', fn ($q) => $q->where('firm_id', $firmId))
            ->where('event_date', '>=', now()->subDays(30))
            ->count();

        return [
            Stat::make('Casos Activos', $activeCases)
                ->description("{$totalCases} en total")
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('gray')
                ->url(route('filament.admin.resources.legal-cases.index')),
            Stat::make('Clientes', $totalClients)
                ->description('Registrados')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray')
                ->url(route('filament.admin.resources.clients.index')),
            Stat::make('Actuaciones', $recentEvents)
                ->description('Ultimos 30 dias')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('gray')
                ->url(route('filament.admin.resources.case-events.index')),
        ];
    }
}
