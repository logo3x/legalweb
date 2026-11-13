<?php

namespace App\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Aviso de que la prueba termina y la cuenta pasara al plan Gratuito con limites.
 */
class PlanLimitsNoticeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const STAGE_FIRST = 'first';

    public const STAGE_FINAL = 'final';

    public function __construct(
        public Carbon $startsAt,
        public string $stage,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date = $this->startsAt->format('d/m/Y');
        $isFinal = $this->stage === self::STAGE_FINAL;

        return (new MailMessage)
            ->subject($isFinal ? 'En 3 dias su cuenta pasa al plan Gratuito - LegalWeb' : 'Su prueba de LegalWeb termina pronto')
            ->view('emails.legalweb-mail', [
                'tone' => $isFinal ? 'warning' : 'info',
                'eyebrow' => $isFinal ? 'Ultimo aviso' : 'Su prueba gratuita',
                'heading' => $isFinal ? 'En 3 dias su cuenta pasa al plan Gratuito' : 'Su prueba gratuita termina pronto',
                'intro' => "A partir del <strong>{$date}</strong> su cuenta pasa al plan <strong>Gratuito</strong>. Sus casos, clientes y documentos se conservan completos.",
                'lines' => [
                    'El plan Gratuito incluye hasta <strong>5 casos activos</strong>, 1 usuario, 10 consultas de IA al mes y sincronizacion semanal con la Rama Judicial.',
                    'La vigilancia de clientes por cedula o NIT queda incluida solo en los planes Profesional y Firma.',
                ],
                'highlight' => 'Con el plan Profesional ($120.000/mes, o $600.000 por 6 meses) sigue con casos ilimitados, 3 usuarios y todas las funciones.',
                'actionUrl' => url('/admin/planes'),
                'actionLabel' => 'Ver planes',
                'footerNote' => 'Si tiene dudas, responda este correo y le ayudamos.',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->stage === self::STAGE_FINAL ? 'En 3 dias su cuenta pasa al plan Gratuito' : 'Su prueba gratuita termina pronto')
            ->body('Desde el '.$this->startsAt->format('d/m/Y').' aplican los limites del plan Gratuito (5 casos activos, 1 usuario).')
            ->icon('heroicon-o-credit-card')
            ->iconColor($this->stage === self::STAGE_FINAL ? 'warning' : 'info')
            ->actions([
                Action::make('ver')
                    ->label('Ver planes')
                    ->url(url('/admin/planes'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
