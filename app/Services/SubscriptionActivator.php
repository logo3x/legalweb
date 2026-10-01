<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

/**
 * Activa una suscripcion a partir de una transaccion de Wompi.
 *
 * Lo usan el webhook, el callback y la verificacion periodica, para que los
 * tres apliquen las mismas reglas: solo suscripciones pendientes, transaccion
 * aprobada, misma referencia y mismo monto/moneda que se cobro en el checkout.
 */
class SubscriptionActivator
{
    /**
     * @param  array<string, mixed>  $transaction
     */
    public function activate(Subscription $subscription, array $transaction): bool
    {
        $reference = $subscription->wompi_reference;

        // Idempotente: un evento repetido o antiguo no reactiva suscripciones vencidas o canceladas.
        if ($subscription->status !== 'pending') {
            Log::info('Wompi: suscripcion no pendiente, se ignora la activacion', ['reference' => $reference, 'status' => $subscription->status]);

            return false;
        }

        if (($transaction['status'] ?? null) !== 'APPROVED' || ($transaction['reference'] ?? null) !== $reference) {
            return false;
        }

        if (($transaction['currency'] ?? null) !== 'COP') {
            Log::warning('Wompi: moneda inesperada', ['reference' => $reference, 'currency' => $transaction['currency'] ?? null]);

            return false;
        }

        $paidInCents = (int) ($transaction['amount_in_cents'] ?? -1);

        if ($subscription->amount_in_cents === null) {
            // Suscripciones creadas antes de guardar el monto esperado.
            Log::warning('Wompi: suscripcion sin monto esperado, se activa sin validar monto', ['reference' => $reference, 'paid' => $paidInCents]);
        } elseif ($paidInCents !== (int) $subscription->amount_in_cents) {
            Log::warning('Wompi: monto pagado distinto al esperado', [
                'reference' => $reference,
                'expected' => $subscription->amount_in_cents,
                'paid' => $paidInCents,
            ]);

            return false;
        }

        Subscription::where('firm_id', $subscription->firm_id)
            ->where('id', '!=', $subscription->id)
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        $subscription->update([
            'status' => 'active',
            'wompi_subscription_id' => $transaction['id'] ?? null,
            'wompi_metadata' => $transaction,
        ]);

        Log::info('Wompi: suscripcion activada', ['reference' => $reference]);

        return true;
    }
}
