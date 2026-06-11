<?php

namespace App\Console\Commands;

use App\Models\CaseEvent;
use App\Models\LegalCase;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:cleanup-duplicate-events {--case= : ID del caso especifico, vacio para todos}')]
#[Description('Elimina actuaciones duplicadas (mismo titulo + misma fecha + mismo caso). Mantiene el registro mas antiguo.')]
class CleanupDuplicateEvents extends Command
{
    public function handle(): int
    {
        $caseId = $this->option('case');

        $query = LegalCase::query()->withoutGlobalScopes();
        if ($caseId) {
            $query->where('id', $caseId);
        }

        $totalRemoved = 0;
        $totalCases = 0;

        foreach ($query->cursor() as $case) {
            // Agrupar por (titulo, fecha) dentro del caso. Si hay grupos con mas
            // de un registro, mantener el de id mas bajo y eliminar el resto.
            $groups = CaseEvent::where('legal_case_id', $case->id)
                ->selectRaw('title, DATE(event_date) as event_day, COUNT(*) as cnt, MIN(id) as keep_id')
                ->groupBy('title', 'event_day')
                ->having('cnt', '>', 1)
                ->get();

            if ($groups->isEmpty()) {
                continue;
            }

            $caseRemoved = 0;

            foreach ($groups as $g) {
                $removed = CaseEvent::where('legal_case_id', $case->id)
                    ->where('title', $g->title)
                    ->whereRaw('DATE(event_date) = ?', [$g->event_day])
                    ->where('id', '!=', $g->keep_id)
                    ->delete();

                $caseRemoved += $removed;
            }

            if ($caseRemoved > 0) {
                $totalRemoved += $caseRemoved;
                $totalCases++;
                $this->info("Caso {$case->case_number}: {$caseRemoved} duplicado(s) eliminado(s)");
            }
        }

        $this->info('---');
        $this->info("Total: {$totalRemoved} duplicado(s) eliminado(s) en {$totalCases} caso(s)");

        return Command::SUCCESS;
    }
}
