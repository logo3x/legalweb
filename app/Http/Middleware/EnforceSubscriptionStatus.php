<?php

namespace App\Http\Middleware;

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

        if (! $user || $user->role === 'superadmin' || ! $user->firm_id) {
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

        // Las escrituras de Livewire (POST /livewire/update) no pasan por aqui:
        // las bloquea el guardian de modelos de AppServiceProvider.
        if (! $user->firm?->isReadOnly()) {
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
