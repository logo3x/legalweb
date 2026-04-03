<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;

class WompiController extends Controller
{
    /**
     * Generar enlace de pago Wompi para un plan.
     * Requiere configurar WOMPI_PUBLIC_KEY y WOMPI_PRIVATE_KEY en .env
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        $firm = auth()->user()->firm;

        $amount = $validated['billing_cycle'] === 'yearly'
            ? $plan->price_yearly
            : $plan->price_monthly;

        $reference = 'LW-'.$firm->id.'-'.now()->timestamp;

        // TODO: Integrar con API de Wompi cuando se tengan las credenciales
        // Documentacion: https://docs.wompi.co/docs/colombia/
        //
        // $response = Http::withHeaders([
        //     'Authorization' => 'Bearer ' . config('services.wompi.private_key'),
        // ])->post('https://production.wompi.co/v1/payment_links', [
        //     'name' => "Plan {$plan->name} - LegalWeb",
        //     'description' => "Suscripcion {$validated['billing_cycle']} al plan {$plan->name}",
        //     'single_use' => true,
        //     'collect_shipping' => false,
        //     'currency' => 'COP',
        //     'amount_in_cents' => $amount * 100,
        //     'redirect_url' => route('wompi.callback'),
        //     'reference' => $reference,
        // ]);
        //
        // return redirect($response->json('data.url'));

        return back()->with('info', 'La pasarela de pago Wompi sera habilitada proximamente.');
    }

    /**
     * Callback de Wompi despues del pago.
     */
    public function callback(Request $request)
    {
        // TODO: Verificar firma del webhook con WOMPI_EVENTS_SECRET
        // $signature = $request->header('X-Event-Checksum');
        //
        // Verificar transaccion:
        // $response = Http::get("https://production.wompi.co/v1/transactions/{$transactionId}");
        // $transaction = $response->json('data');
        //
        // if ($transaction['status'] === 'APPROVED') {
        //     Activar suscripcion...
        // }

        return redirect('/admin')->with('success', 'Pago procesado correctamente.');
    }

    /**
     * Webhook de Wompi para eventos asincrónos.
     */
    public function webhook(Request $request)
    {
        // TODO: Implementar cuando se tengan credenciales
        // Eventos: transaction.updated, nequi_token.updated
        //
        // $event = $request->json('event');
        // $data = $request->json('data.transaction');
        //
        // if ($event === 'transaction.updated' && $data['status'] === 'APPROVED') {
        //     $reference = $data['reference'];
        //     $subscription = Subscription::where('wompi_reference', $reference)->first();
        //     if ($subscription) {
        //         $subscription->update(['status' => 'active']);
        //     }
        // }

        return response()->json(['status' => 'ok']);
    }
}
