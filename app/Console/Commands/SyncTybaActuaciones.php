<?php

namespace App\Console\Commands;

use App\Jobs\SyncCaseActuaciones;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\Scopes\FirmScope;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

#[Signature('app:sync-tyba-actuaciones {--retry-failed : Solo reintenta los casos cuya ultima consulta fallo}')]
#[Description('Sincroniza actuaciones desde la Rama Judicial y SAMAI para casos con radicado')]
class SyncTybaActuaciones extends Command
{
    /**
     * Momento de la ultima corrida nocturna completa (lo vigila app:notify-sync-failures).
     */
    public const LAST_RUN_KEY = 'sync.nightly.last_run_at';

    /**
     * Reintentos por caso antes de esperar a la corrida nocturna siguiente.
     */
    public const MAX_RETRIES = 6;

    public function handle(): void
    {
        if ($this->option('retry-failed')) {
            $this->retryFailed();

            return;
        }

        // Casos activos con radicado sin una consulta exitosa en 24 h. Un fallo no actualiza
        // last_tyba_sync, asi que los casos que fallaron tambien entran aqui.
        $cases = $this->activeCases()
            ->where(function ($q) {
                $q->whereNull('last_tyba_sync')
                    ->orWhere('last_tyba_sync', '<', now()->subHours(24));
            })
            ->get();

        $this->info("Casos a sincronizar: {$cases->count()}");

        $dispatched = 0;

        $syncEveryDays = [];

        foreach ($cases as $case) {
            // Plan Gratuito: sincronizacion automatica semanal.
            $syncEveryDays[$case->firm_id] ??= Firm::find($case->firm_id)?->planLimits()->syncEveryDays() ?? 1;

            if ($syncEveryDays[$case->firm_id] > 1 && $case->last_tyba_sync && $case->last_tyba_sync->gt(now()->subDays($syncEveryDays[$case->firm_id]))) {
                continue;
            }

            // Delay de 5 segundos entre cada consulta para no saturar la API
            SyncCaseActuaciones::dispatch($case)->delay(now()->addSeconds($dispatched * 5));
            $dispatched++;

            $this->info("Encolado: {$case->case_number} ({$case->external_case_number})");
        }

        Cache::forever(self::LAST_RUN_KEY, now()->toDateTimeString());

        $this->info("Total encolados: {$dispatched}");
    }

    /**
     * Reintento (cada hora, desde el cron): casos cuya ultima consulta fallo hace mas de una hora.
     */
    private function retryFailed(): void
    {
        $cases = $this->activeCases()
            ->whereNotNull('sync_failed_at')
            ->where('sync_failed_at', '<', now()->subHour())
            ->where('sync_failures', '<', self::MAX_RETRIES)
            ->orderBy('sync_failed_at')
            ->limit(50)
            ->get();

        foreach ($cases->values() as $index => $case) {
            SyncCaseActuaciones::dispatch($case, 'automatico')->delay(now()->addSeconds($index * 5));
        }

        $this->info("Reintentos encolados: {$cases->count()}");
    }

    /**
     * @return Builder<LegalCase>
     */
    private function activeCases()
    {
        return LegalCase::withoutGlobalScope(FirmScope::class)
            ->whereNotNull('external_case_number')
            ->where('external_case_number', '!=', '')
            ->whereIn('status', ['abierto', 'en_progreso', 'en_espera']);
    }
}
