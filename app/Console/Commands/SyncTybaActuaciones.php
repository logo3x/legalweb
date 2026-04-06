<?php

namespace App\Console\Commands;

use App\Jobs\SyncCaseActuaciones;
use App\Models\Firm;
use App\Models\LegalCase;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:sync-tyba-actuaciones')]
#[Description('Sincroniza actuaciones desde Tyba para casos con radicado')]
class SyncTybaActuaciones extends Command
{
    public function handle(): void
    {
        if (! config('services.twocaptcha.api_key')) {
            $this->warn('2Captcha API key no configurada. Abortando.');

            return;
        }

        // Resetear contadores mensuales
        Firm::where('tyba_queries_reset_at', '<', now()->startOfMonth())
            ->orWhereNull('tyba_queries_reset_at')
            ->update([
                'tyba_queries_used' => 0,
                'tyba_queries_reset_at' => now(),
            ]);

        // Obtener casos con radicado que no se han sincronizado en 24h
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
            $firm = $case->user?->firm;

            if (! $firm) {
                continue;
            }

            $plan = $firm->activeSubscription?->plan;
            $maxQueries = $plan?->max_tyba_queries ?? 0;

            if ($maxQueries > 0 && $firm->tyba_queries_used >= $maxQueries) {
                $this->info("Firma {$firm->name}: sin creditos ({$firm->tyba_queries_used}/{$maxQueries})");

                continue;
            }

            // Despachar con delay de 45 segundos entre cada uno
            SyncCaseActuaciones::dispatch($case)->delay(now()->addSeconds($dispatched * 45));
            $dispatched++;

            $this->info("Encolado: {$case->case_number} ({$case->external_case_number})");
        }

        $this->info("Total encolados: {$dispatched}");
    }
}
