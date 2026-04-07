<?php

namespace App\Console\Commands;

use App\Jobs\SyncCaseActuaciones;
use App\Models\LegalCase;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:sync-tyba-actuaciones')]
#[Description('Sincroniza actuaciones desde la Rama Judicial para casos con radicado')]
class SyncTybaActuaciones extends Command
{
    public function handle(): void
    {
        // Obtener casos activos con radicado que no se han sincronizado en 24h
        $cases = LegalCase::withoutGlobalScopes()
            ->whereNotNull('external_case_number')
            ->where('external_case_number', '!=', '')
            ->whereIn('status', ['abierto', 'en_progreso', 'en_espera'])
            ->where(function ($q) {
                $q->whereNull('last_tyba_sync')
                    ->orWhere('last_tyba_sync', '<', now()->subHours(24));
            })
            ->get();

        $this->info("Casos a sincronizar: {$cases->count()}");

        $dispatched = 0;

        foreach ($cases as $case) {
            // Delay de 5 segundos entre cada consulta para no saturar la API
            SyncCaseActuaciones::dispatch($case)->delay(now()->addSeconds($dispatched * 5));
            $dispatched++;

            $this->info("Encolado: {$case->case_number} ({$case->external_case_number})");
        }

        $this->info("Total encolados: {$dispatched}");
    }
}
