<?php

namespace App\Services;

use App\Models\Firm;
use App\Models\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Cobro automatico de la renovacion con una tarjeta registrada (fuentes de pago de Wompi).
 *
 * El numero de la tarjeta nunca pasa por LegalWeb: el navegador la tokeniza directamente con
 * Wompi (llave publica) y aqui solo llega el token, con el que se crea la fuente de pago
 * (llave privada). La renovacion se cobra con esa fuente y se activa con el mismo
 * SubscriptionActivator del webhook y de la verificacion periodica.
 */
class WompiRecurringService
{
    public static function enabled(): bool
    {
        return (bool) config('services.wompi.recurring_enabled')
            && filled(config('services.wompi.public_key'))
            && filled(config('services.wompi.private_key'))
            && filled(config('services.wompi.integrity_secret'));
    }

    private function baseUrl(): string
    {
        return (string) config('services.wompi.base_url');
    }

    /**
     * Contratos que el usuario debe aceptar antes de registrar la tarjeta (terminos de Wompi y
     * autorizacion de datos personales).
     *
     * @return array{acceptance_token: string, acceptance_permalink: string, personal_auth_token: string, personal_auth_permalink: string}
     */
    public function acceptanceTokens(): array
    {
        $response = Http::timeout(15)
            ->withHeaders(['x-merchant-public-key' => (string) config('services.wompi.public_key')])
            ->get($this->baseUrl().'/merchants/info');

        $data = $response->json('data');

        if (! $response->successful() || ! isset($data['presigned_acceptance']['acceptance_token'], $data['presigned_personal_data_auth']['acceptance_token'])) {
            throw new RuntimeException('Wompi no entrego los contratos de aceptacion (HTTP '.$response->status().').');
        }

        return [
            'acceptance_token' => (string) $data['presigned_acceptance']['acceptance_token'],
            'acceptance_permalink' => (string) ($data['presigned_acceptance']['permalink'] ?? ''),
            'personal_auth_token' => (string) $data['presigned_personal_data_auth']['acceptance_token'],
            'personal_auth_permalink' => (string) ($data['presigned_personal_data_auth']['permalink'] ?? ''),
        ];
    }

    /**
     * Crea la fuente de pago con el token de la tarjeta y la deja asociada a la firma.
     */
    public function registerCard(Firm $firm, string $cardToken, string $email, string $acceptanceToken, string $personalAuthToken): void
    {
        $response = Http::timeout(20)
            ->withToken((string) config('services.wompi.private_key'))
            ->post($this->baseUrl().'/payment_sources', [
                'type' => 'CARD',
                'token' => $cardToken,
                'customer_email' => $email,
                'acceptance_token' => $acceptanceToken,
                'accept_personal_auth' => $personalAuthToken,
            ]);

        $source = $response->json('data');

        if (! $response->successful() || ($source['status'] ?? null) !== 'AVAILABLE' || empty($source['id'])) {
            Log::warning('Wompi: no se pudo registrar la tarjeta', ['firm' => $firm->id, 'status' => $response->status(), 'source_status' => $source['status'] ?? null]);

            throw new RuntimeException('Wompi no acepto la tarjeta. Revise los datos o intente con otra.');
        }

        $firm->forceFill([
            'wompi_payment_source_id' => (string) $source['id'],
            'card_brand' => mb_substr((string) ($source['public_data']['brand'] ?? $source['public_data']['type'] ?? 'Tarjeta'), 0, 30),
            'card_last_four' => mb_substr((string) ($source['public_data']['last_four'] ?? ''), 0, 4),
            'card_owner_email' => $email,
            'auto_renew_authorized_at' => now(),
            'auto_renew_failures' => 0,
        ])->save();
    }

    /**
     * Desactiva el cobro automatico y olvida la fuente de pago.
     */
    public function removeCard(Firm $firm): void
    {
        $firm->forceFill([
            'wompi_payment_source_id' => null,
            'card_brand' => null,
            'card_last_four' => null,
            'card_owner_email' => null,
            'auto_renew_authorized_at' => null,
            'auto_renew_failures' => 0,
        ])->save();
    }

    /**
     * Cobra la renovacion de la suscripcion activa con la tarjeta registrada. Crea una suscripcion
     * pendiente (como el checkout) que se activa cuando Wompi aprueba el pago.
     *
     * @return array{subscription: Subscription, status: string}
     */
    public function chargeRenewal(Firm $firm, Subscription $current): array
    {
        $plan = $current->plan;

        if (! $plan || (int) $plan->price_monthly <= 0 || ! $firm->hasAutoRenewCard()) {
            throw new RuntimeException('La firma no tiene un plan de pago con tarjeta registrada.');
        }

        // Precio vigente del plan, sin el descuento del primer pago.
        $amount = $current->billing_cycle === 'biannual' ? (int) $plan->price_yearly : (int) $plan->price_monthly;
        $amountInCents = $amount * 100;
        $currency = 'COP';
        $reference = strtoupper((string) config('services.wompi.origin', 'legalweb')).'-'.$firm->id.'-'.$plan->slug.'-renov-'.now()->timestamp.'-'.Str::lower(Str::random(6));
        $startsAt = $current->ends_at && $current->ends_at->isFuture() ? $current->ends_at->copy() : now();

        $renewal = Subscription::create([
            'firm_id' => $firm->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $current->billing_cycle,
            'amount_in_cents' => $amountInCents,
            'status' => 'pending',
            'is_auto_renewal' => true,
            'starts_at' => $startsAt,
            'ends_at' => $current->billing_cycle === 'biannual' ? $startsAt->copy()->addMonths(6) : $startsAt->copy()->addMonth(),
            'wompi_reference' => $reference,
        ]);

        $response = Http::timeout(30)
            ->withToken((string) config('services.wompi.private_key'))
            ->post($this->baseUrl().'/transactions', [
                'amount_in_cents' => $amountInCents,
                'currency' => $currency,
                'signature' => hash('sha256', $reference.$amountInCents.$currency.config('services.wompi.integrity_secret')),
                'customer_email' => $firm->card_owner_email,
                'payment_method' => ['installments' => 1],
                'reference' => $reference,
                'payment_source_id' => (int) $firm->wompi_payment_source_id,
                'recurrent' => true,
            ]);

        $transaction = $response->json('data');
        $status = (string) ($transaction['status'] ?? 'ERROR');

        if (! $response->successful() || ! is_array($transaction)) {
            $renewal->update(['status' => 'canceled']);
            Log::warning('Wompi: cobro automatico rechazado al crear la transaccion', ['firm' => $firm->id, 'http' => $response->status()]);

            return ['subscription' => $renewal, 'status' => 'ERROR'];
        }

        $renewal->update(['wompi_subscription_id' => $transaction['id'] ?? null]);

        if ($status === 'APPROVED') {
            app(SubscriptionActivator::class)->activate($renewal->fresh(), $transaction);
        } elseif (in_array($status, ['DECLINED', 'ERROR', 'VOIDED'], true)) {
            $renewal->update(['status' => 'canceled']);
        }

        // PENDING: lo resuelven el webhook o app:verify-pending-payments con la misma referencia.
        return ['subscription' => $renewal->fresh(), 'status' => $status];
    }
}
