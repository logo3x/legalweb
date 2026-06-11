<?php

namespace App\Notifications;

use App\Models\Subscription;
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

        [$subject, $tone, $eyebrow, $heading, $intro, $lines, $highlight, $actionLabel, $footer] = match ($this->stage) {
            self::STAGE_UPCOMING => [
                'Su suscripcion se renueva pronto - LegalWeb',
                'info',
                'Renovacion proxima',
                "Dr(a). {$name}, su suscripcion vence pronto",
                "Su plan <strong>{$plan->name}</strong> vence el <strong>{$ends}</strong> ({$price} COP/mes).",
                ['Para que su cuenta siga activa sin interrupciones, realice el pago antes de esa fecha.'],
                null,
                'Renovar ahora',
                'Si tiene dudas, responda este correo y le ayudamos.',
            ],
            self::STAGE_OVERDUE => [
                'Su suscripcion vencio - tiene 3 dias de gracia',
                'warning',
                'Pago pendiente',
                'Su suscripcion vencio',
                "Vencio el <strong>{$ends}</strong>. Le dimos 3 dias adicionales para que pueda regularizar el pago sin perder acceso.",
                [],
                "Monto a pagar: <strong>{$price} COP</strong>.",
                'Pagar ahora',
                'Despues de 3 dias sin pago, su cuenta entrara a modo solo-lectura.',
            ],
            self::STAGE_GRACE_ENDING => [
                'Su cuenta entrara a solo-lectura manana - LegalWeb',
                'danger',
                'Ultimo aviso',
                'Manana su cuenta entra a solo-lectura',
                'Su periodo de gracia termina manana.',
                ['Si no realiza el pago, manana su cuenta entrara a modo <strong>solo-lectura</strong>: podra ver sus casos pero no crear ni editar nada.'],
                null,
                'Pagar ahora para evitar suspension',
                'Los datos siguen seguros. Puede reactivar pagando en cualquier momento.',
            ],
            self::STAGE_SUSPENDED => [
                'Cuenta en modo solo-lectura por falta de pago',
                'danger',
                'Cuenta limitada',
                'Su cuenta esta en modo solo-lectura',
                'La suscripcion no fue renovada, asi que entramos en modo solo-lectura.',
                ['Puede consultar sus casos pero no crear ni editar nuevos registros. La sincronizacion automatica con la Rama Judicial tambien esta pausada.'],
                null,
                'Reactivar suscripcion',
                'Sus datos estan completos. En 30 dias sin pago archivaremos la cuenta y le avisaremos antes.',
            ],
            default => [
                'Aviso sobre su suscripcion - LegalWeb',
                'info',
                null,
                'Aviso sobre su suscripcion',
                'Hay un cambio en el estado de su suscripcion.',
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
            self::STAGE_UPCOMING => ['Su suscripcion se renueva pronto', 'info'],
            self::STAGE_OVERDUE => ['Su suscripcion vencio - 3 dias de gracia', 'warning'],
            self::STAGE_GRACE_ENDING => ['Su cuenta sera limitada manana', 'danger'],
            self::STAGE_SUSPENDED => ['Cuenta en solo-lectura por falta de pago', 'danger'],
        ];

        [$title, $color] = $messages[$this->stage] ?? ['Aviso de suscripcion', 'info'];

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
