<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Informe de verificacion de los lunes: proceso por proceso, cuando fue la ultima consulta
 * exitosa a la Rama Judicial o SAMAI. El silencio nunca significa "sin novedades": si un
 * proceso no se pudo consultar, aparece aqui de primero.
 */
class VerificationReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private const MAX_LISTED = 80;

    /**
     * @param  list<array{case_number: string, radicado: string, title: string, state: string, detail: string, new_events: int}>  $rows
     * @param  array{total: int, ok: int, error: int, stale: int, new_events: int}  $summary
     */
    public function __construct(
        public array $rows,
        public array $summary,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->summary;
        $attention = $s['error'] + $s['stale'];

        $mail = (new MailMessage)
            ->subject($attention > 0
                ? "Informe de verificación: {$attention} de {$s['total']} procesos requieren revisión"
                : "Informe de verificación: sus {$s['total']} procesos al día")
            ->greeting("Hola {$notifiable->name}")
            ->line('Este es el informe semanal de lo que LegalWeb consultó por usted en la Rama Judicial y SAMAI.')
            ->line("**{$s['ok']}** al día · **{$s['error']}** con error de consulta · **{$s['stale']}** sin consulta reciente · **{$s['new_events']}** actuaciones nuevas en la semana.");

        if ($attention > 0) {
            $mail->line('Los procesos con error o sin consulta reciente van primero: revíselos directamente en la Rama Judicial o en SAMAI, porque puede haber actuaciones que LegalWeb no ve.');
        }

        foreach (array_slice($this->rows, 0, self::MAX_LISTED) as $row) {
            $icon = match ($row['state']) {
                'error' => '🔴',
                'stale' => '🟠',
                default => '🟢',
            };
            $events = $row['new_events'] > 0 ? " · {$row['new_events']} actuación(es) nueva(s)" : '';
            $mail->line("{$icon} **{$row['case_number']}** · {$row['radicado']} — {$row['detail']}{$events}");
        }

        if (count($this->rows) > self::MAX_LISTED) {
            $mail->line('… y '.(count($this->rows) - self::MAX_LISTED).' proceso(s) más en su panel.');
        }

        return $mail
            ->action('Ver mis casos', url('/admin/legal-cases'))
            ->line('Recibe este informe cada lunes. Si un proceso falla entre semana, le avisamos el mismo día.');
    }
}
