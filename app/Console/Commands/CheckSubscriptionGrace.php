<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\SubscriptionStatusNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:check-subscription-grace')]
#[Description('Revisa estado de suscripciones y dispara notificaciones de vencimiento. Diario.')]
class CheckSubscriptionGrace extends Command
{
    public function handle(): int
    {
        // Solo procesamos suscripciones activas (no canceladas, no pending)
        $subs = Subscription::with('firm.users', 'plan')
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->get();

        $upcoming = 0;
        $overdue = 0;
        $graceEnding = 0;
        $suspended = 0;

        foreach ($subs as $sub) {
            $ends = $sub->ends_at;
            $now = now();
            $daysToExpiry = (int) $now->diffInDays($ends, false);

            $stage = null;

            // 5 dias antes de vencer
            if ($daysToExpiry === 5 && $sub->warning_stage !== SubscriptionStatusNotification::STAGE_UPCOMING) {
                $stage = SubscriptionStatusNotification::STAGE_UPCOMING;
            }

            // Vencido (dia mismo o pasado), iniciar gracia de 3 dias
            if ($ends->isPast() && $sub->warning_stage !== SubscriptionStatusNotification::STAGE_OVERDUE && $sub->warning_stage !== SubscriptionStatusNotification::STAGE_GRACE_ENDING && $sub->warning_stage !== SubscriptionStatusNotification::STAGE_SUSPENDED) {
                $stage = SubscriptionStatusNotification::STAGE_OVERDUE;
                $sub->grace_until = $ends->copy()->addDays(3);
            }

            // 1 dia antes de que termine gracia
            if ($sub->grace_until && $sub->grace_until->isFuture()
                && (int) $now->diffInDays($sub->grace_until, false) === 1
                && $sub->warning_stage !== SubscriptionStatusNotification::STAGE_GRACE_ENDING
                && $sub->warning_stage !== SubscriptionStatusNotification::STAGE_SUSPENDED) {
                $stage = SubscriptionStatusNotification::STAGE_GRACE_ENDING;
            }

            // Gracia agotada: suspender
            if ($sub->grace_until && $sub->grace_until->isPast()
                && $sub->warning_stage !== SubscriptionStatusNotification::STAGE_SUSPENDED) {
                $stage = SubscriptionStatusNotification::STAGE_SUSPENDED;
                $sub->status = 'past_due';
            }

            if (! $stage) {
                continue;
            }

            $owner = $sub->firm?->users?->first();
            if (! $owner) {
                continue;
            }

            $owner->notify(new SubscriptionStatusNotification($sub, $stage));

            $sub->warning_stage = $stage;
            $sub->last_warning_sent_at = $now;
            $sub->save();

            match ($stage) {
                SubscriptionStatusNotification::STAGE_UPCOMING => $upcoming++,
                SubscriptionStatusNotification::STAGE_OVERDUE => $overdue++,
                SubscriptionStatusNotification::STAGE_GRACE_ENDING => $graceEnding++,
                SubscriptionStatusNotification::STAGE_SUSPENDED => $suspended++,
                default => null,
            };
        }

        $this->info("Avisos enviados: proximo {$upcoming} | vencido {$overdue} | gracia termina {$graceEnding} | suspendido {$suspended}");

        return Command::SUCCESS;
    }
}
