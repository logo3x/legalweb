<?php

namespace App\Console\Commands;

use App\Models\Firm;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\AutoRenewFailedNotification;
use App\Services\WompiRecurringService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:charge-auto-renewals')]
#[Description('Cobra la renovacion con la tarjeta registrada un dia antes de vencer (maximo 3 intentos)')]
class ChargeAutoRenewals extends Command
{
    public const MAX_ATTEMPTS = 3;

    public function handle(WompiRecurringService $recurring): int
    {
        if (! WompiRecurringService::enabled()) {
            $this->info('Cobro automatico desactivado (WOMPI_RECURRING_ENABLED).');

            return self::SUCCESS;
        }

        // Suscripciones de pago que vencen en menos de un dia o estan en los 3 dias de gracia.
        $due = Subscription::with(['firm', 'plan'])
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now()->subDays(3), now()->addDay()])
            ->whereHas('plan', fn ($query) => $query->where('price_monthly', '>', 0))
            ->get();

        $charged = 0;

        foreach ($due as $current) {
            $firm = $current->firm;

            if (! $firm || ! $firm->hasAutoRenewCard()) {
                continue;
            }

            $lastRenewal = Subscription::where('firm_id', $firm->id)
                ->where('is_auto_renewal', true)
                ->where('id', '!=', $current->id)
                ->where('created_at', '>=', $current->created_at)
                ->latest('id')
                ->first();

            // Un cobro en curso o hecho hace menos de 20 horas: no se repite.
            if ($lastRenewal && ($lastRenewal->status === 'pending' || $lastRenewal->created_at->gt(now()->subHours(20)))) {
                continue;
            }

            // El intento anterior fue rechazado despues (lo cancelo la verificacion de pagos): se cuenta y se avisa.
            if ($lastRenewal && $lastRenewal->status === 'canceled' && ! ($lastRenewal->wompi_metadata['failure_counted'] ?? false)) {
                $this->registerFailure($firm, $lastRenewal);
            }

            if ($firm->auto_renew_failures >= self::MAX_ATTEMPTS) {
                continue;
            }

            try {
                $result = $recurring->chargeRenewal($firm, $current);
            } catch (\Throwable $e) {
                Log::warning('Cobro automatico fallo', ['firm' => $firm->id, 'error' => $e->getMessage()]);

                continue;
            }

            $charged++;

            if (in_array($result['status'], ['DECLINED', 'ERROR', 'VOIDED'], true)) {
                $this->registerFailure($firm, $result['subscription']);
            }

            $this->info("Firma {$firm->id}: cobro {$result['status']}");
        }

        $this->info("Cobros automaticos intentados: {$charged}");

        return self::SUCCESS;
    }

    private function registerFailure(Firm $firm, Subscription $renewal): void
    {
        $firm->forceFill(['auto_renew_failures' => $firm->auto_renew_failures + 1])->save();
        $renewal->forceFill(['wompi_metadata' => [...($renewal->wompi_metadata ?? []), 'failure_counted' => true]])->save();

        $willRetry = $firm->auto_renew_failures < self::MAX_ATTEMPTS;

        foreach (User::where('firm_id', $firm->id)->where('role', 'admin')->get() as $admin) {
            try {
                $admin->notify(new AutoRenewFailedNotification($firm, $willRetry));
            } catch (\Throwable $e) {
                Log::warning('Aviso de cobro fallido no enviado', ['firm' => $firm->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
