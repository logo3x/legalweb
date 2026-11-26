<?php

namespace App\Console\Commands;

use App\Models\CaseFlowProgress;
use App\Notifications\TermDeadlineNotification;
use App\Services\JudicialCalendarService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:check-deadlines')]
#[Description('Verifica terminos proximos a vencer y notifica a los abogados')]
class CheckDeadlines extends Command
{
    /**
     * Avisos por etapa: dias habiles restantes en que se avisa, del mas urgente al menos.
     */
    private const STAGES = [0, 1, 3];

    public function handle(JudicialCalendarService $calendar): void
    {
        // Solo pasos iniciados: el plazo corre en dias habiles desde que el abogado inicia el paso.
        $progress = CaseFlowProgress::with(['legalCase.user', 'flowStep'])
            ->where('status', 'en_progreso')
            ->whereNotNull('started_at')
            ->whereHas('flowStep', fn ($q) => $q->whereNotNull('days_limit')->where('days_limit', '>', 0))
            ->get();

        $notified = 0;

        foreach ($progress as $item) {
            $case = $item->legalCase;
            $step = $item->flowStep;

            if (! $case || ! $step || ! $case->user || $case->trashed()) {
                continue;
            }

            $deadline = $calendar->calculateDeadline($item->started_at, (int) $step->days_limit)['deadline'];
            $remaining = $calendar->businessDaysBetween(now(), $deadline);
            $sent = array_map('intval', $item->deadline_notices ?? []);

            // El aviso mas urgente que aplique y no se haya enviado. Si un dia no corrio el cron,
            // se envia el que corresponda hoy (no se pierde) y se dan por cubiertos los anteriores.
            $stage = collect(self::STAGES)->first(fn (int $s): bool => $remaining <= $s && ! in_array($s, $sent, true));

            if ($stage === null) {
                continue;
            }

            try {
                $case->user->notify(new TermDeadlineNotification($case, $item, $remaining, $deadline));
            } catch (\Throwable $e) {
                Log::warning('Aviso de termino no enviado', ['progress' => $item->id, 'error' => $e->getMessage()]);

                continue;
            }

            $covered = array_filter(self::STAGES, fn (int $s): bool => $s >= $stage);
            $item->forceFill(['deadline_notices' => array_values(array_unique([...$sent, ...$covered]))])->saveQuietly();
            $notified++;

            $this->info("Notificado: {$case->case_number} - {$step->name} ({$remaining} dias habiles)");
        }

        $this->info("Total notificaciones enviadas: {$notified}");
    }
}
