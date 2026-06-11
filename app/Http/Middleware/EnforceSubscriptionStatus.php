<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea escrituras cuando la suscripcion de la firma esta vencida
 * y el periodo de gracia ya termino. El usuario sigue pudiendo
 * consultar todo, pero no puede crear, editar ni eliminar.
 *
 * Excepciones siempre permitidas:
 *  - Rutas de pago (/wompi/*, /admin/planes)
 *  - Logout
 *  - Superadmin (puede operar siempre)
 */
class EnforceSubscriptionStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->is_super_admin || ! $user->firm_id) {
            return $next($request);
        }

        // Solo bloqueamos escrituras (no GET/HEAD/OPTIONS)
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $path = $request->path();
        $allowedPrefixes = [
            'wompi/',
            'admin/planes',
            'admin/logout',
            'logout',
            'admin/tour/',
        ];
        foreach ($allowedPrefixes as $allowed) {
            if (str_starts_with($path, $allowed)) {
                return $next($request);
            }
        }

        $sub = Subscription::where('firm_id', $user->firm_id)
            ->whereIn('status', ['active', 'past_due'])
            ->latest('id')
            ->first();

        if (! $sub) {
            return $next($request);
        }

        // Read-only si: status past_due o (active con gracia vencida)
        $isReadOnly = $sub->status === 'past_due'
            || ($sub->grace_until && $sub->grace_until->isPast() && $sub->ends_at && $sub->ends_at->isPast());

        if (! $isReadOnly) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Suscripcion vencida. Su cuenta esta en modo solo-lectura. Renueve para volver a operar.',
            ], 402);
        }

        return redirect('/admin/planes')->with('error', 'Su suscripcion esta vencida. Renueve para crear o editar registros.');
    }
}
