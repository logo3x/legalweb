<?php

namespace App\Http\Controllers;

use App\Models\Firm;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Consultas que n8n hace a LegalWeb (cabecera Authorization: Bearer N8N_API_TOKEN).
 *
 * Pensado para automatizaciones de marketing: bienvenida, reactivacion de
 * firmas inactivas y seguimiento de pruebas por vencer.
 */
class N8nController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $this->authorizeToken($request);

        return response()->json(['status' => 'ok', 'app' => 'legalweb', 'time' => now()->toIso8601String()]);
    }

    /**
     * Lista de firmas con metricas de uso.
     *
     * Filtros opcionales:
     *  - inactive_days=N   el administrador no inicia sesion hace N dias o mas
     *  - without_cases=1   firmas sin casos reales (sin contar los de ejemplo)
     *  - registered_days=N firmas registradas en los ultimos N dias
     *  - trial_ending_days=N prueba gratuita que vence en los proximos N dias
     *  - include_opted_out=1 incluye administradores que pidieron no recibir correos masivos
     */
    public function firms(Request $request): JsonResponse
    {
        $this->authorizeToken($request);

        $validated = $request->validate([
            'inactive_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'without_cases' => ['nullable', 'boolean'],
            'registered_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'trial_ending_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'include_opted_out' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $firms = Firm::query()
            ->with(['activeSubscription.plan'])
            ->withCount(['legalCases as real_cases_count' => fn ($q) => $q->withoutGlobalScopes()->where('is_demo', false)->whereNull('deleted_at')])
            ->withMax(['legalCases as last_case_created_at' => fn ($q) => $q->withoutGlobalScopes()->where('is_demo', false)], 'created_at')
            ->when($validated['registered_days'] ?? null, fn ($q, $days) => $q->where('created_at', '>=', now()->subDays((int) $days)))
            ->when($request->boolean('without_cases'), fn ($q) => $q->whereDoesntHave('legalCases', fn ($c) => $c->withoutGlobalScopes()->where('is_demo', false)->whereNull('deleted_at')))
            ->when(isset($validated['trial_ending_days']), fn ($q) => $q->whereHas('activeSubscription', fn ($s) => $s
                ->whereNotNull('trial_ends_at')
                ->whereBetween('trial_ends_at', [now(), now()->addDays((int) $validated['trial_ending_days'])->endOfDay()])))
            ->orderByDesc('created_at')
            ->limit((int) ($validated['limit'] ?? 200))
            ->get();

        $admins = User::query()
            ->whereIn('firm_id', $firms->pluck('id'))
            ->whereIn('role', ['admin', 'superadmin'])
            ->orderBy('id')
            ->get()
            ->groupBy('firm_id')
            ->map(fn ($users) => $users->first());

        $inactiveDays = $validated['inactive_days'] ?? null;
        $includeOptedOut = $request->boolean('include_opted_out');

        $data = $firms
            ->map(function (Firm $firm) use ($admins): array {
                $admin = $admins->get($firm->id);
                $subscription = $firm->activeSubscription;

                return [
                    'id' => $firm->id,
                    'name' => $firm->name,
                    'email' => $firm->email,
                    'phone' => $firm->phone,
                    'city' => $firm->city,
                    'registered_at' => $firm->created_at?->toIso8601String(),
                    'onboarding_completed' => (bool) $firm->onboarding_completed,
                    'real_cases' => (int) $firm->real_cases_count,
                    'last_case_created_at' => $firm->last_case_created_at,
                    'plan' => $subscription?->plan?->name,
                    'subscription_status' => $subscription?->status,
                    'trial_ends_at' => $subscription?->trial_ends_at?->toDateString(),
                    'ends_at' => $subscription?->ends_at?->toDateString(),
                    'admin' => $admin ? [
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'last_login_at' => $admin->last_login_at?->toIso8601String(),
                        'login_count' => (int) $admin->login_count,
                        'marketing_opt_out' => $admin->mass_email_opt_out_at !== null,
                    ] : null,
                ];
            })
            ->filter(function (array $firm) use ($inactiveDays, $includeOptedOut): bool {
                if (! $includeOptedOut && ($firm['admin']['marketing_opt_out'] ?? false)) {
                    return false;
                }

                if ($inactiveDays) {
                    $lastLogin = $firm['admin']['last_login_at'] ?? null;

                    return ! $lastLogin || now()->diffInDays($lastLogin, true) >= $inactiveDays;
                }

                return true;
            })
            ->values();

        return response()->json(['count' => $data->count(), 'data' => $data]);
    }

    private function authorizeToken(Request $request): void
    {
        $token = (string) config('services.n8n.api_token');

        if (strlen($token) < 20 || ! hash_equals($token, (string) $request->bearerToken())) {
            abort(401, 'Token invalido');
        }
    }
}
