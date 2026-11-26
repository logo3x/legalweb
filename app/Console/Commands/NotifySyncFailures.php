<?php

namespace App\Console\Commands;

use App\Models\LegalCase;
use App\Models\Scopes\FirmScope;
use App\Models\User;
use App\Notifications\SyncFailuresNotification;
use App\Services\SystemNews;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

#[Signature('app:notify-sync-failures')]
#[Description('Avisa a cada abogado los procesos que no se pudieron consultar y al superadmin si la sincronizacion nocturna no corre o falla en masa')]
class NotifySyncFailures extends Command
{
    /**
     * Porcentaje de procesos fallidos desde el que se avisa al superadmin (caida de la fuente).
     */
    private const MASS_FAILURE_RATIO = 0.3;

    public function handle(): int
    {
        $failing = LegalCase::withoutGlobalScope(FirmScope::class)
            ->with('user')
            ->whereNotNull('sync_failed_at')
            ->whereNotNull('external_case_number')
            ->where('is_demo', false)
            ->whereIn('status', ['abierto', 'en_progreso', 'en_espera'])
            ->orderBy('sync_failed_at')
            ->get();

        $notified = 0;

        foreach ($failing->groupBy(fn (LegalCase $case): int => (int) ($case->user_id ?? 0)) as $userId => $cases) {
            $recipients = $userId ? collect([$cases->first()->user])->filter() : collect();

            // Casos sin abogado responsable: se avisa a los administradores de la firma.
            if ($recipients->isEmpty()) {
                $recipients = User::where('firm_id', $cases->first()->firm_id)->where('role', 'admin')->get();
            }

            foreach ($recipients as $recipient) {
                // Un aviso por persona al dia: si el fallo sigue, se repite al dia siguiente.
                if (! Cache::add("sync-failures-notified:{$recipient->id}:".now()->toDateString(), true, now()->endOfDay())) {
                    continue;
                }

                try {
                    $recipient->notify(new SyncFailuresNotification($cases->values()));
                    $notified++;
                } catch (\Throwable $e) {
                    Log::warning('Aviso de fallos de sincronizacion no enviado', ['user' => $recipient->id, 'error' => $e->getMessage()]);
                }
            }
        }

        $this->checkPlatformHealth($failing->count());

        $this->info("Procesos con error de consulta: {$failing->count()} | avisos enviados: {$notified}");

        return self::SUCCESS;
    }

    /**
     * Avisa al superadmin si la tarea nocturna no corrio o si la fuente judicial fallo en masa.
     */
    private function checkPlatformHealth(int $failingCount): void
    {
        $activeCount = LegalCase::withoutGlobalScope(FirmScope::class)
            ->whereNotNull('external_case_number')
            ->where('external_case_number', '!=', '')
            ->where('is_demo', false)
            ->whereIn('status', ['abierto', 'en_progreso', 'en_espera'])
            ->count();

        if ($activeCount === 0) {
            return;
        }

        $lastRun = Cache::get(SyncTybaActuaciones::LAST_RUN_KEY);

        if (! $lastRun || Carbon::parse($lastRun)->lt(now()->subHours(26))) {
            SystemNews::notifySuperadmins(
                'La sincronización nocturna no corrió',
                [
                    $lastRun
                        ? 'La última revisión automática de procesos fue el '.Carbon::parse($lastRun)->format('d/m/Y H:i').'.'
                        : 'No hay registro de ninguna revisión automática de procesos.',
                    "Hay {$activeCount} procesos activos esperando revisión. Revise en cPanel que el cron de sync-tyba (3:00 a.m.) y el de queue (cada minuto) sigan activos.",
                ],
                'Abrir el panel',
                url('/admin'),
                onceKey: 'sync.nightly.missing.'.now()->toDateString(),
                onceForDays: 1,
            );
        }

        if ($failingCount >= 5 && $failingCount / $activeCount >= self::MASS_FAILURE_RATIO) {
            SystemNews::notifySuperadmins(
                'Falla masiva al consultar procesos',
                [
                    "{$failingCount} de {$activeCount} procesos activos no se pudieron consultar en la última revisión.",
                    'Suele ser una caída o un cambio en la Rama Judicial o en SAMAI. Cada abogado ya recibió el aviso de revisar sus procesos a mano; LegalWeb reintenta cada hora.',
                ],
                'Abrir el panel',
                url('/admin'),
                onceKey: 'sync.mass_failure.'.now()->toDateString(),
                onceForDays: 1,
            );
        }
    }
}
