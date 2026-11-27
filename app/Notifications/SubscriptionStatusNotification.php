<?php

namespace App\Notifications;

use App\Models\Subscription;
use App\Services\WompiRecurringService;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const STAGE_UPCOMING = 'upcoming';

    public const STAGE_OVERDUE = 'overdue';

    public const STAGE_GRACE_ENDING = 'grace_ending';

    public const STAGE_SUSPENDED = 'suspended';

    public function __construct(
        public Subscription $subscription,
        public string $stage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $plan = $this->subscription->plan;
        $price = '$'.number_format($plan->price_monthly ?? 120000, 0, ',', '.');
        $ends = $this->subscription->ends_at?->format('d/m/Y') ?? '-';

        $name = $notifiable->name ?? '';

        // Con tarjeta registrada y cobro automatico activo, el aviso informa el cobro en vez de pedir el pago.
        $firm = $this->subscription->firm;
        $autoRenew = $firm && $firm->hasAutoRenewCard() && WompiRecurringService::enabled();
        $card = trim(($firm?->card_brand ?? 'tarjeta').' terminada en '.($firm?->card_last_four ?? ''));

        [$subject, $tone, $eyebrow, $heading, $intro, $lines, $highlight, $actionLabel, $footer] = match ($this->stage) {
            self::STAGE_UPCOMING => [
                'Su suscripción se renueva pronto - LegalWeb',
                'info',
                'Renovación próxima',
                "Dr(a). {$name}, su suscripción vence pronto",
                "Su plan <strong>{$plan->name}</strong> vence el <strong>{$ends}</strong> ({$price} COP/mes).",
                [$autoRenew
                    ? 'Le cobraremos la renovación automáticamente a su '.e($card).' el día anterior al vencimiento; en su extracto aparecerá como '.e(WompiRecurringService::statementName()).'. No tiene que hacer nada; si prefiere otro medio de pago, puede quitar la tarjeta en Mi Plan.'
                    : 'Para que su cuenta siga activa sin interrupciones, realice el pago antes de esa fecha.'],
                null,
                'Renovar ahora',
                'Si tiene dudas, responda este correo y le ayudamos.',
            ],
            self::STAGE_OVERDUE => [
                'Su suscripción venció - tiene 3 días de gracia',
                'warning',
                'Pago pendiente',
                'Su suscripción venció',
                "Venció el <strong>{$ends}</strong>. Le dimos 3 días adicionales para que pueda regularizar el pago sin perder acceso.",
                [],
                "Monto a pagar: <strong>{$price} COP</strong>.",
                'Pagar ahora',
                'Después de 3 días sin pago, su cuenta entrará a modo solo lectura.',
            ],
            self::STAGE_GRACE_ENDING => [
                'Su cuenta entrará a solo lectura mañana - LegalWeb',
                'danger',
                'Último aviso',
                'Mañana su cuenta entra a solo lectura',
                'Su periodo de gracia termina mañana.',
                ['Si no realiza el pago, mañana su cuenta entrará a modo <strong>solo lectura</strong>: podrá ver sus casos pero no crear ni editar nada.'],
                null,
                'Pagar ahora para evitar suspensión',
                'Los datos siguen seguros. Puede reactivar pagando en cualquier momento.',
            ],
            self::STAGE_SUSPENDED => [
                'Cuenta en modo solo lectura por falta de pago',
                'danger',
                'Cuenta limitada',
                'Su cuenta está en modo solo lectura',
                'La suscripción no fue renovada, así que entramos en modo solo lectura.',
                ['Puede consultar sus casos pero no crear ni editar nuevos registros. La sincronización automática con la Rama Judicial también está pausada.'],
                null,
                'Reactivar suscripción',
                'Sus datos están completos. En 30 días sin pago archivaremos la cuenta y le avisaremos antes.',
            ],
            default => [
                'Aviso sobre su suscripción - LegalWeb',
                'info',
                null,
                'Aviso sobre su suscripción',
                'Hay un cambio en el estado de su suscripción.',
                [],
                null,
                'Revisar Mi Plan',
                null,
            ],
        };

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.legalweb-mail', [
                'tone' => $tone,
                'eyebrow' => $eyebrow,
                'heading' => $heading,
                'intro' => $intro,
                'lines' => $lines,
                'highlight' => $highlight,
                'actionUrl' => url('/admin/planes'),
                'actionLabel' => $actionLabel,
                'footerNote' => $footer,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $messages = [
            self::STAGE_UPCOMING => ['Su suscripción se renueva pronto', 'info'],
            self::STAGE_OVERDUE => ['Su suscripción venció - 3 días de gracia', 'warning'],
            self::STAGE_GRACE_ENDING => ['Su cuenta será limitada mañana', 'danger'],
            self::STAGE_SUSPENDED => ['Cuenta en solo lectura por falta de pago', 'danger'],
        ];

        [$title, $color] = $messages[$this->stage] ?? ['Aviso de suscripción', 'info'];

        return \Filament\Notifications\Notification::make()
            ->title($title)
            ->body("Plan {$this->subscription->plan->name}")
            ->icon('heroicon-o-credit-card')
            ->iconColor($color)
            ->actions([
                Action::make('ver')
                    ->label('Ir a Mi Plan')
                    ->url(url('/admin/planes'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
