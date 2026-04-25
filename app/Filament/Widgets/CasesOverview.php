<?php

namespace App\Filament\Widgets;

use App\Models\CaseEvent;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\Reminder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CasesOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $firmId = auth()->user()->firm_id;
        $userId = auth()->id();

        $totalCases = LegalCase::where('firm_id', $firmId)->count();
        $activeCases = LegalCase::where('firm_id', $firmId)->whereIn('status', ['abierto', 'en_progreso'])->count();
        $totalClients = Client::where('firm_id', $firmId)->count();
        $recentEvents = CaseEvent::whereHas('legalCase', fn ($q) => $q->where('firm_id', $firmId))
            ->where('event_date', '>=', now()->subDays(30))
            ->count();

        // Alertas urgentes (vencimientos proximos 7 dias)
        $urgentReminders = Reminder::where('user_id', $userId)
            ->where('is_completed', false)
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(7))
            ->count();

        $overdueReminders = Reminder::where('user_id', $userId)
            ->where('is_completed', false)
            ->where('due_date', '<', now())
            ->count();

        $alertDescription = $overdueReminders > 0
            ? "{$overdueReminders} vencido(s)"
            : 'Proximos 7 dias';

        $alertColor = match (true) {
            $overdueReminders > 0 => 'danger',
            $urgentReminders > 3 => 'warning',
            default => 'success',
        };

        return [
            Stat::make('Casos Activos', $activeCases)
                ->description("{$totalCases} en total")
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary')
                ->url(route('filament.admin.resources.legal-cases.index')),
            Stat::make('Clientes', $totalClients)
                ->description('Registrados')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->url(route('filament.admin.resources.clients.index')),
            Stat::make('Actuaciones', $recentEvents)
                ->description('Ultimos 30 dias')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning')
                ->url(route('filament.admin.resources.case-events.index')),
            Stat::make('Alertas', $urgentReminders + $overdueReminders)
                ->description($alertDescription)
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color($alertColor)
                ->url(route('filament.admin.resources.reminders.index')),
        ];
    }
}
