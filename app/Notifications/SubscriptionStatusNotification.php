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

        $mail = (new MailMessage)
            ->greeting("Dr(a). {$notifiable->name}");

        return match ($this->stage) {
            self::STAGE_UPCOMING => $mail
                ->subject('Su suscripcion se renueva pronto - LegalWeb')
                ->line("Su suscripcion al plan {$plan->name} vence el **{$ends}** ({$price} COP/mes).")
                ->line('Para que su cuenta siga activa sin interrupciones, realice el pago antes de esa fecha.')
                ->action('Renovar ahora', url('/admin/planes'))
                ->line('Si tiene dudas, responda este correo y le ayudamos.'),

            self::STAGE_OVERDUE => $mail
                ->subject('Su suscripcion vencio - tiene 3 dias de gracia')
                ->line("Su suscripcion vencio el **{$ends}**. Le dimos 3 dias adicionales para que pueda regularizar el pago sin perder acceso.")
                ->line("Monto a pagar: **{$price} COP**.")
                ->action('Pagar ahora', url('/admin/planes'))
                ->line('Despues de 3 dias sin pago, su cuenta entrara a modo solo-lectura.'),

            self::STAGE_GRACE_ENDING => $mail
                ->subject('Su cuenta entrara a solo-lectura manana - LegalWeb')
                ->error()
                ->line('Su periodo de gracia termina manana.')
                ->line('Si no realiza el pago, manana su cuenta entrara a modo **solo-lectura**: podra ver sus casos pero no crear ni editar nada.')
                ->action('Pagar ahora para evitar suspension', url('/admin/planes'))
                ->line('Los datos siguen seguros. Puede reactivar pagando en cualquier momento.'),

            self::STAGE_SUSPENDED => $mail
                ->subject('Cuenta en modo solo-lectura por falta de pago')
                ->error()
                ->line('Su cuenta entro a modo solo-lectura porque la suscripcion no fue renovada.')
                ->line('Puede consultar sus casos pero no crear ni editar nuevos registros. La sincronizacion automatica con la Rama Judicial tambien esta pausada.')
                ->action('Reactivar suscripcion', url('/admin/planes'))
                ->line('Sus datos estan completos. En 30 dias sin pago archivaremos la cuenta y le avisaremos antes.'),

            default => $mail
                ->subject('Aviso sobre su suscripcion - LegalWeb')
                ->line('Hay un cambio en el estado de su suscripcion.')
                ->action('Revisar Mi Plan', url('/admin/planes')),
        };
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
