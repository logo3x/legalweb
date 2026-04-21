<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WompiController extends Controller
{
    private function baseUrl(): string
    {
        return config('services.wompi.base_url');
    }

    private function checkoutUrl(): string
    {
        return config('services.wompi.sandbox')
            ? 'https://checkout.wompi.co/widget2/test'
            : 'https://checkout.wompi.co/widget2';
    }

    /**
     * Prefijo de reference para identificar pagos de esta app.
     * Compartido con otras apps en la misma cuenta Wompi.
     */
    private function originPrefix(): string
    {
        return strtoupper(config('services.wompi.origin', 'legalweb')).'-';
    }

    /**
     * Redirige al Widget de Checkout de Wompi.
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,biannual',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        $firm = auth()->user()->firm;

        if (! $firm) {
            return redirect('/admin/planes')->with('error', 'Debe configurar su firma primero.');
        }

        $amount = $validated['billing_cycle'] === 'biannual'
            ? $plan->price_yearly
            : $plan->price_monthly;

        $amountInCents = $amount * 100;
        $reference = $this->originPrefix().$firm->id.'-'.$plan->slug.'-'.now()->timestamp;
        $currency = 'COP';

        // Crear suscripcion pendiente
        Subscription::create([
            'firm_id' => $firm->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $validated['billing_cycle'],
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => $validated['billing_cycle'] === 'biannual'
                ? now()->addMonths(6)
                : now()->addMonth(),
            'wompi_reference' => $reference,
        ]);

        // Generar firma de integridad
        $integritySecret = config('services.wompi.integrity_secret');
        $signatureString = $reference.$amountInCents.$currency.$integritySecret;
        $signature = hash('sha256', $signatureString);

        // Redirect al checkout de Wompi
        $publicKey = config('services.wompi.public_key');
        $redirectUrl = route('wompi.callback');

        $checkoutParams = http_build_query([
            'public-key' => $publicKey,
            'currency' => $currency,
            'amount-in-cents' => $amountInCents,
            'reference' => $reference,
            'redirect-url' => $redirectUrl,
            'signature:integrity' => $signature,
        ]);

        return redirect($this->checkoutUrl().'?'.$checkoutParams);
    }

    /**
     * Callback despues de que el usuario paga.
     */
    public function callback(Request $request)
    {
        $transactionId = $request->query('id');

        if (! $transactionId) {
            return redirect('/admin/planes')->with('info', 'Transaccion no encontrada o cancelada.');
        }

        $response = Http::get($this->baseUrl().'/transactions/'.$transactionId);

        if ($response->successful()) {
            $transaction = $response->json('data');
            $status = $transaction['status'] ?? 'UNKNOWN';
            $reference = $transaction['reference'] ?? '';

            if ($status === 'APPROVED' && str_starts_with($reference, $this->originPrefix())) {
                $subscription = Subscription::where('wompi_reference', $reference)->first();

                if ($subscription) {
                    // Desactivar suscripciones anteriores
                    Subscription::where('firm_id', $subscription->firm_id)
                        ->where('id', '!=', $subscription->id)
                        ->where('status', 'active')
                        ->update(['status' => 'expired']);

                    $subscription->update([
                        'status' => 'active',
                        'wompi_subscription_id' => $transactionId,
                        'wompi_metadata' => $transaction,
                    ]);

                    return redirect('/admin')->with('success', 'Pago aprobado. Su plan ha sido activado.');
                }
            }

            if ($status === 'DECLINED' || $status === 'ERROR' || $status === 'VOIDED') {
                Subscription::where('wompi_reference', $reference)->update(['status' => 'canceled']);

                return redirect('/admin/planes')->with('error', 'El pago fue rechazado. Intente con otro medio de pago.');
            }

            if ($status === 'PENDING') {
                return redirect('/admin')->with('info', 'Su pago esta siendo procesado. Le notificaremos cuando se confirme.');
            }
        }

        return redirect('/admin/planes')->with('info', 'No pudimos verificar el pago. Si realizo el pago, se activara automaticamente.');
    }

    /**
     * Webhook de Wompi para eventos asincronos.
     */
    public function webhook(Request $request)
    {
        $data = $request->json('data.transaction', []);
        $reference = $data['reference'] ?? '';

        // Filtro: ignorar pagos de otras apps que comparten la misma cuenta Wompi
        if (! str_starts_with($reference, $this->originPrefix())) {
            return response()->json(['status' => 'ignored']);
        }

        // Verificar firma (OBLIGATORIO)
        $signature = $request->json('signature.checksum');
        $properties = $request->json('signature.properties', []);
        $timestamp = $request->json('timestamp');

        if (! $signature || empty($properties) || ! $timestamp) {
            Log::warning('Wompi webhook: firma faltante', ['reference' => $reference]);

            return response()->json(['status' => 'missing_signature'], 401);
        }

        $values = collect($properties)->map(fn ($prop) => data_get($request->json(), "data.transaction.{$prop}"))->implode('');
        $expectedSignature = hash('sha256', $values.$timestamp.config('services.wompi.events_secret'));

        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('Wompi webhook: firma invalida', ['reference' => $reference]);

            return response()->json(['status' => 'invalid_signature'], 401);
        }

        $event = $request->json('event');
        $status = $data['status'] ?? '';

        if ($event === 'transaction.updated' && $status === 'APPROVED') {
            $subscription = Subscription::where('wompi_reference', $reference)->first();

            if ($subscription) {
                Subscription::where('firm_id', $subscription->firm_id)
                    ->where('id', '!=', $subscription->id)
                    ->where('status', 'active')
                    ->update(['status' => 'expired']);

                $subscription->update([
                    'status' => 'active',
                    'wompi_subscription_id' => $data['id'] ?? null,
                    'wompi_metadata' => $data,
                ]);

                Log::info('Wompi webhook: suscripcion activada', ['reference' => $reference]);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
