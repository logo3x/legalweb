<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:cleanup-duplicate-reminders {--firm= : ID de firma especifica, vacio para todas}')]
#[Description('Elimina recordatorios duplicados (mismo titulo + mismo due_date + mismo caso). Mantiene el registro mas antiguo.')]
class CleanupDuplicateReminders extends Command
{
    public function handle(): int
    {
        $firmId = $this->option('firm');

        $base = Reminder::query();
        if ($firmId) {
            $base->where('firm_id', $firmId);
        }

        // Detectar duplicados por la tupla (firm_id, legal_case_id, title, due_date)
        // — incluye los huerfanos sin caso usando NULL safe.
        $duplicates = DB::table('reminders')
            ->selectRaw('firm_id, COALESCE(legal_case_id, 0) as case_key, title, due_date, COUNT(*) as cnt, MIN(id) as keep_id')
            ->when($firmId, fn ($q) => $q->where('firm_id', $firmId))
            ->groupBy('firm_id', 'case_key', 'title', 'due_date')
            ->having('cnt', '>', 1)
            ->get();

        $totalRemoved = 0;

        foreach ($duplicates as $g) {
            $query = Reminder::where('firm_id', $g->firm_id)
                ->where('title', $g->title)
                ->where('due_date', $g->due_date)
                ->where('id', '!=', $g->keep_id);

            if ((int) $g->case_key === 0) {
                $query->whereNull('legal_case_id');
            } else {
                $query->where('legal_case_id', (int) $g->case_key);
            }

            $removed = $query->delete();
            $totalRemoved += $removed;

            $this->info("'{$g->title}' (vence {$g->due_date}): {$removed} duplicado(s) eliminado(s)");
        }

        $this->info('---');
        $this->info("Total: {$totalRemoved} recordatorio(s) duplicado(s) eliminado(s)");

        return Command::SUCCESS;
    }
}
