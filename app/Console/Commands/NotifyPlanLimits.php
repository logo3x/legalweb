<?php

namespace App\Console\Commands;

use App\Models\Firm;
use App\Models\Subscription;
use App\Notifications\PlanLimitsNoticeNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:notify-plan-limits')]
#[Description('Avisa a las firmas 30 y 3 dias antes de que apliquen los limites del plan Gratuito')]
class NotifyPlanLimits extends Command
{
    public function handle(): int
    {
        $firmIds = Subscription::where('status', 'active')
            ->whereHas('plan', fn ($query) => $query->where('price_monthly', 0))
            ->pluck('firm_id')
            ->unique();

        $sent = 0;

        foreach (Firm::whereIn('id', $firmIds)->get() as $firm) {
            $limits = $firm->planLimits();

            if ($limits->tier() !== 'trial') {
                continue;
            }

            $startsAt = $limits->limitsStartAt();
            $daysLeft = (int) now()->startOfDay()->diffInDays($startsAt->copy()->startOfDay(), false);

            $stage = match (true) {
                $daysLeft <= 3 && ! $firm->plan_limits_final_notice_at => PlanLimitsNoticeNotification::STAGE_FINAL,
                $daysLeft <= 30 && ! $firm->plan_limits_notice_at => PlanLimitsNoticeNotification::STAGE_FIRST,
                default => null,
            };

            if (! $stage) {
                continue;
            }

            $admins = $firm->users()->whereIn('role', ['admin'])->get();

            foreach ($admins as $admin) {
                try {
                    $admin->notify(new PlanLimitsNoticeNotification($startsAt, $stage));
                } catch (\Throwable $e) {
                    Log::warning('Aviso de limites de plan fallo: '.$e->getMessage(), ['firm_id' => $firm->id]);
                }
            }

            // El aviso final tambien cuenta como primero si nunca se envio el de 30 dias.
            $firm->forceFill(array_filter([
                'plan_limits_notice_at' => $firm->plan_limits_notice_at ?? now(),
                'plan_limits_final_notice_at' => $stage === PlanLimitsNoticeNotification::STAGE_FINAL ? now() : null,
            ]))->saveQuietly();

            $sent++;
        }

        $this->info("Avisos de limites enviados a {$sent} firma(s).");

        return self::SUCCESS;
    }
}
