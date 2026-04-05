<?php

namespace App\Console\Commands;

use App\Models\CaseFlowProgress;
use App\Notifications\TermDeadlineNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:check-deadlines')]
#[Description('Verifica terminos proximos a vencer y notifica a los abogados')]
class CheckDeadlines extends Command
{
    public function handle(): void
    {
        $progress = CaseFlowProgress::with(['legalCase.user', 'flowStep'])
            ->whereIn('status', ['pendiente', 'en_progreso'])
            ->whereHas('flowStep', fn ($q) => $q->whereNotNull('days_limit'))
            ->get();

        $notified = 0;

        foreach ($progress as $item) {
            $case = $item->legalCase;
            $step = $item->flowStep;

            if (! $case || ! $step->days_limit || ! $case->user) {
                continue;
            }

            // Calcular dias desde que el paso esta activo
            $startDate = $item->updated_at ?? $item->created_at;
            $daysElapsed = $startDate->diffInDays(now());
            $daysRemaining = $step->days_limit - $daysElapsed;

            // Notificar a 3 dias, 1 dia y el mismo dia
            if (in_array($daysRemaining, [3, 1, 0])) {
                $case->user->notify(new TermDeadlineNotification($case, $item, $daysRemaining));
                $notified++;
                $this->info("Notificado: {$case->case_number} - {$step->name} ({$daysRemaining} dias)");
            }
        }

        $this->info("Total notificaciones enviadas: {$notified}");
    }
}
