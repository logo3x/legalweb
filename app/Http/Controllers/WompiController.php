<?php

namespace App\Http\Controllers;

use App\Models\DiscountCode;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionActivator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            'discount_code' => 'nullable|string|max:40',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        $firm = auth()->user()->firm;

        if (! $firm) {
            return redirect('/admin/planes')->with('error', 'Debe configurar su firma primero.');
        }

        $originalAmount = $validated['billing_cycle'] === 'biannual'
            ? $plan->price_yearly
            : $plan->price_monthly;

        // Validar codigo de descuento si viene
        $discountCode = null;
        $discountAmount = 0;
        if (! empty($validated['discount_code'])) {
            $code = strtoupper(trim($validated['discount_code']));
            $discountCode = DiscountCode::where('code', $code)->first();

            if (! $discountCode) {
                return redirect('/admin/planes')->with('error', 'El codigo de descuento no existe.');
            }
            $error = $discountCode->validateForPlan($plan);
            if ($error) {
                return redirect('/admin/planes')->with('error', $error);
            }
            $discountAmount = $discountCode->calculateDiscount($originalAmount);
        }

        $finalAmount = max(0, $originalAmount - $discountAmount);
        $amountInCents = $finalAmount * 100;
        // Sufijo aleatorio: dos checkouts en el mismo segundo (doble clic) no deben compartir referencia.
        $reference = $this->originPrefix().$firm->id.'-'.$plan->slug.'-'.now()->timestamp.'-'.Str::lower(Str::random(6));
        $currency = 'COP';

        // Crear suscripcion pendiente
        Subscription::create([
            'firm_id' => $firm->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $validated['billing_cycle'],
            'amount_in_cents' => $amountInCents,
            'discount_code_id' => $discountCode?->id,
            'original_amount' => $discountCode ? $originalAmount : null,
            'discount_amount' => $discountCode ? $discountAmount : null,
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => $validated['billing_cycle'] === 'biannual'
                ? now()->addMonths(6)
                : now()->addMonth(),
            'wompi_reference' => $reference,
        ]);

        // El canje del codigo se registra en SubscriptionActivator cuando el pago se aprueba,
        // para que abrir el checkout sin pagar no consuma usos del codigo.

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

                if ($subscription && ($subscription->status === 'active' || app(SubscriptionActivator::class)->activate($subscription, $transaction))) {
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
        // El webhook principal llega a https://citora.com.co/webhook/wompi
        // Citora debe reenviar los que no son suyos, o verificamos via polling (verify-payments)
        if (! str_starts_with($reference, $this->originPrefix())) {
            Log::info('Wompi webhook: pago de otra app ignorado', ['reference' => $reference]);

            return response()->json(['status' => 'ignored', 'reason' => 'different_origin']);
        }

        // Verificar firma (OBLIGATORIO)
        $signature = $request->json('signature.checksum');
        $properties = $request->json('signature.properties', []);
        $timestamp = $request->json('timestamp');

        if (! $signature || empty($properties) || ! $timestamp) {
            Log::warning('Wompi webhook: firma faltante', ['reference' => $reference]);

            return response()->json(['status' => 'missing_signature'], 401);
        }

        // Sin secreto, cualquiera podria calcular una firma valida.
        $eventsSecret = (string) config('services.wompi.events_secret');

        if ($eventsSecret === '') {
            Log::error('Wompi webhook: WOMPI_EVENTS_SECRET no configurado, se rechaza el evento', ['reference' => $reference]);

            return response()->json(['status' => 'not_configured'], 503);
        }

        // Las propiedades vienen relativas a "data" (p. ej. "transaction.id" => data.transaction.id).
        $values = collect($properties)->map(fn ($prop) => data_get($request->json('data'), $prop))->implode('');
        $expectedSignature = hash('sha256', $values.$timestamp.$eventsSecret);

        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('Wompi webhook: firma invalida', ['reference' => $reference]);

            return response()->json(['status' => 'invalid_signature'], 401);
        }

        $event = $request->json('event');
        $status = $data['status'] ?? '';

        if ($event === 'transaction.updated' && $status === 'APPROVED') {
            $subscription = Subscription::where('wompi_reference', $reference)->first();

            if ($subscription) {
                app(SubscriptionActivator::class)->activate($subscription, $data);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
