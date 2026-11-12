<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\Firm;
use App\Models\FirmInvitation;
use App\Models\LegalCase;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Lo que puede hacer una firma segun su plan.
 *
 * - paid: suscripcion activa a un plan de pago (Profesional o Firma): limites de ese plan.
 * - trial: prueba de 3 meses (o el plazo de aviso antes de aplicar limites): como Profesional.
 * - free: plan Gratuito despues de la prueba: limites reducidos.
 * - unmanaged: firma sin suscripcion (creada a mano por el superadmin): sin limites.
 */
class FirmPlan
{
    /**
     * Las firmas cuya prueba ya habia terminado reciben 30 dias de aviso:
     * los limites del plan Gratuito no se aplican antes de esta fecha.
     */
    public const FREE_LIMITS_FROM = '2026-12-12';

    /**
     * Estados de caso que cuentan como activos.
     */
    public const ACTIVE_STATUSES = ['abierto', 'en_progreso', 'en_espera'];

    private ?Subscription $subscription = null;

    private bool $resolved = false;

    public function __construct(public Firm $firm) {}

    public function subscription(): ?Subscription
    {
        if (! $this->resolved) {
            $this->resolved = true;

            $active = Subscription::with('plan')
                ->where('firm_id', $this->firm->getKey())
                ->where('status', 'active')
                ->latest('id')
                ->get();

            $this->subscription = $active->first(fn (Subscription $sub): bool => ($sub->plan?->price_monthly ?? 0) > 0)
                ?? $active->first();
        }

        return $this->subscription;
    }

    public function plan(): ?Plan
    {
        return $this->subscription()?->plan;
    }

    /**
     * @return 'paid'|'trial'|'free'|'unmanaged'
     */
    public function tier(): string
    {
        $subscription = $this->subscription();

        if (! $subscription || ! $subscription->plan) {
            return 'unmanaged';
        }

        if ($subscription->plan->price_monthly > 0) {
            return 'paid';
        }

        return now()->lt($this->limitsStartAt()) ? 'trial' : 'free';
    }

    /**
     * Fecha desde la que aplican los limites del plan Gratuito.
     */
    public function limitsStartAt(): Carbon
    {
        $subscription = $this->subscription();
        $trialEnd = $subscription?->trial_ends_at ?? $subscription?->starts_at ?? $this->firm->created_at ?? now();

        return Carbon::parse($trialEnd)->max(Carbon::parse(self::FREE_LIMITS_FROM));
    }

    public function isLimited(): bool
    {
        return $this->tier() === 'free';
    }

    /**
     * Plan cuyos limites se aplican: el contratado, el Profesional durante la prueba,
     * o ninguno (sin limites) para firmas sin suscripcion.
     */
    public function effectivePlan(): ?Plan
    {
        return match ($this->tier()) {
            'paid', 'free' => $this->plan(),
            'trial' => Plan::whereIn('slug', ['pro', 'profesional'])->first() ?? $this->plan(),
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this->tier()) {
            'paid' => $this->plan()?->name ?? 'Profesional',
            'trial' => 'Prueba gratuita',
            'free' => 'Gratuito',
            default => 'Sin plan',
        };
    }

    /**
     * Casos activos permitidos (null = sin limite).
     */
    public function maxActiveCases(): ?int
    {
        if ($this->tier() === 'trial') {
            return null;
        }

        $max = $this->effectivePlan()?->max_cases;

        return $max ? (int) $max : null;
    }

    public function maxUsers(): ?int
    {
        $plan = $this->effectivePlan();

        if (! $plan) {
            return null;
        }

        return $this->tier() === 'trial' ? max(3, (int) $plan->max_users) : max(1, (int) $plan->max_users);
    }

    public function aiMonthlyLimit(): ?int
    {
        return $this->tier() === 'trial' ? null : $this->effectivePlan()?->ai_monthly_limit;
    }

    public function hasWhatsApp(): bool
    {
        return $this->tier() === 'trial' || (bool) ($this->effectivePlan()?->has_whatsapp ?? true);
    }

    public function hasClientMonitoring(): bool
    {
        return $this->tier() === 'trial' || (bool) ($this->effectivePlan()?->has_client_monitoring ?? true);
    }

    public function syncEveryDays(): int
    {
        return $this->tier() === 'trial' ? 1 : max(1, (int) ($this->effectivePlan()?->sync_every_days ?? 1));
    }

    public function activeCasesCount(): int
    {
        return LegalCase::withoutGlobalScopes()
            ->where('firm_id', $this->firm->getKey())
            ->whereNull('deleted_at')
            ->where('is_demo', false)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->count();
    }

    public function canAddActiveCase(int $howMany = 1): bool
    {
        $max = $this->maxActiveCases();

        return $max === null || $this->activeCasesCount() + $howMany <= $max;
    }

    /**
     * Usuarios de la firma mas invitaciones pendientes.
     */
    public function seatsUsed(): int
    {
        return User::where('firm_id', $this->firm->getKey())->count()
            + FirmInvitation::where('firm_id', $this->firm->getKey())->where('status', 'pending')->count();
    }

    public function canInviteUser(): bool
    {
        $max = $this->maxUsers();

        return $max === null || $this->seatsUsed() < $max;
    }

    public function aiUsedThisMonth(): int
    {
        return AiUsageLog::where('firm_id', $this->firm->getKey())
            ->where('success', true)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    public function canUseAi(): bool
    {
        $limit = $this->aiMonthlyLimit();

        return $limit === null || $this->aiUsedThisMonth() < $limit;
    }

    /**
     * Mensaje para mostrar cuando se alcanza un limite.
     */
    public static function upgradeMessage(string $what): string
    {
        return "{$what} Mejore su plan en \"Mi Plan\" para seguir sin limites.";
    }
}
