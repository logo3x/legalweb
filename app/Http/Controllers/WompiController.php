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

    /**
     * Genera un link de pago Wompi para un plan.
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,biannual',
        ]);

        // Si viene por GET, redirigir con datos
        if ($request->isMethod('get') && ! $request->has('_token')) {
            // Permitir GET para links desde Livewire
        }

        $plan = Plan::findOrFail($validated['plan_id']);
        $firm = auth()->user()->firm;

        if (! $firm) {
            return back()->with('error', 'Debe configurar su firma primero.');
        }

        $amount = $validated['billing_cycle'] === 'biannual'
            ? $plan->price_yearly
            : $plan->price_monthly;

        $reference = 'LEGALWEB-'.$firm->id.'-'.$plan->slug.'-'.now()->timestamp;

        // Crear enlace de pago via API de Wompi
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.wompi.private_key'),
        ])->post($this->baseUrl().'/payment_links', [
            'name' => 'Plan '.$plan->name.' - LegalWeb',
            'description' => 'Suscripcion '.($validated['billing_cycle'] === 'biannual' ? 'semestral' : 'mensual').' al plan '.$plan->name,
            'single_use' => true,
            'collect_shipping' => false,
            'currency' => 'COP',
            'amount_in_cents' => $amount * 100,
            'redirect_url' => route('wompi.callback'),
            'customer_data' => [
                'legal_id_type' => 'NIT',
                'legal_id' => $firm->nit ?? '0000000000',
                'full_name' => $firm->name,
                'phone_number' => $firm->phone ?? '0000000000',
                'email' => $firm->email ?? auth()->user()->email,
            ],
        ]);

        if ($response->successful() && $response->json('data.id')) {
            // Guardar referencia en suscripcion pendiente
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

            $paymentUrl = $response->json('data.url')
                ?? 'https://checkout.wompi.co/l/'.$response->json('data.id');

            return redirect($paymentUrl);
        }

        Log::error('Wompi payment link creation failed', [
            'response' => $response->json(),
            'status' => $response->status(),
        ]);

        return back()->with('error', 'No se pudo generar el enlace de pago. Intente nuevamente.');
    }

    /**
     * Callback despues de que el usuario paga (redirect).
     */
    public function callback(Request $request)
    {
        $transactionId = $request->query('id');

        if (! $transactionId) {
            return redirect('/admin')->with('error', 'Transaccion no encontrada.');
        }

        // Consultar estado de la transaccion
        $response = Http::get($this->baseUrl().'/transactions/'.$transactionId);

        if ($response->successful()) {
            $transaction = $response->json('data');
            $status = $transaction['status'] ?? 'UNKNOWN';
            $reference = $transaction['reference'] ?? '';

            if ($status === 'APPROVED' && str_starts_with($reference, 'LEGALWEB-')) {
                $subscription = Subscription::where('wompi_reference', $reference)->first();

                if ($subscription) {
                    $subscription->update([
                        'status' => 'active',
                        'wompi_subscription_id' => $transactionId,
                        'wompi_metadata' => $transaction,
                    ]);

                    return redirect('/admin')->with('success', 'Pago aprobado. Su plan ha sido activado.');
                }
            }

            if ($status === 'DECLINED' || $status === 'ERROR') {
                return redirect('/admin')->with('error', 'El pago fue rechazado. Intente con otro medio de pago.');
            }
        }

        return redirect('/admin')->with('info', 'Estamos procesando su pago. Le notificaremos cuando se confirme.');
    }

    /**
     * Webhook de Wompi para eventos asincronos (transacciones).
     */
    public function webhook(Request $request)
    {
        // Verificar que el evento sea de LegalWeb
        $data = $request->json('data.transaction', []);
        $reference = $data['reference'] ?? '';

        if (! str_starts_with($reference, 'LEGALWEB-')) {
            return response()->json(['status' => 'ignored']);
        }

        // Verificar firma del evento
        $signature = $request->json('signature.checksum');
        $properties = $request->json('signature.properties', []);
        $timestamp = $request->json('timestamp');

        if ($signature && $properties) {
            $values = collect($properties)->map(fn ($prop) => data_get($request->json(), "data.transaction.{$prop}"))->implode('');
            $expectedSignature = hash('sha256', $values.$timestamp.config('services.wompi.events_secret'));

            if ($signature !== $expectedSignature) {
                Log::warning('Wompi webhook: invalid signature', ['reference' => $reference]);

                return response()->json(['status' => 'invalid_signature'], 401);
            }
        }

        $event = $request->json('event');
        $status = $data['status'] ?? '';

        if ($event === 'transaction.updated' && $status === 'APPROVED') {
            $subscription = Subscription::where('wompi_reference', $reference)->first();

            if ($subscription) {
                $subscription->update([
                    'status' => 'active',
                    'wompi_subscription_id' => $data['id'] ?? null,
                    'wompi_metadata' => $data,
                ]);

                Log::info('Wompi webhook: subscription activated', ['reference' => $reference]);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
