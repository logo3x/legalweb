<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

#[Signature('app:verify-pending-payments')]
#[Description('Verifica transacciones pendientes consultando directo a Wompi (por si el webhook no llega)')]
class VerifyPendingPayments extends Command
{
    public function handle(): void
    {
        $origin = strtoupper(config('services.wompi.origin', 'legalweb')).'-';

        $pendings = Subscription::where('status', 'pending')
            ->whereNotNull('wompi_reference')
            ->where('wompi_reference', 'like', "{$origin}%")
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        $this->info("Verificando {$pendings->count()} suscripciones pendientes");

        $verified = 0;

        foreach ($pendings as $sub) {
            try {
                $baseUrl = config('services.wompi.base_url');
                $response = Http::timeout(15)
                    ->get("{$baseUrl}/transactions", [
                        'reference' => $sub->wompi_reference,
                    ]);

                if (! $response->successful()) {
                    continue;
                }

                $transactions = $response->json('data', []);

                if (empty($transactions)) {
                    continue;
                }

                $tx = $transactions[0];
                $status = $tx['status'] ?? 'PENDING';

                if ($status === 'APPROVED') {
                    Subscription::where('firm_id', $sub->firm_id)
                        ->where('id', '!=', $sub->id)
                        ->where('status', 'active')
                        ->update(['status' => 'expired']);

                    $sub->update([
                        'status' => 'active',
                        'wompi_subscription_id' => $tx['id'] ?? null,
                        'wompi_metadata' => $tx,
                    ]);

                    Log::info('Pago verificado via polling', ['reference' => $sub->wompi_reference]);
                    $this->info("Activada: {$sub->wompi_reference}");
                    $verified++;
                } elseif (in_array($status, ['DECLINED', 'ERROR', 'VOIDED'])) {
                    $sub->update(['status' => 'canceled']);
                    $this->warn("Cancelada: {$sub->wompi_reference}");
                }
            } catch (\Exception $e) {
                Log::warning('Error verificando pago: '.$e->getMessage(), ['reference' => $sub->wompi_reference]);
            }
        }

        $this->info("Total verificadas: {$verified}");
    }
}
