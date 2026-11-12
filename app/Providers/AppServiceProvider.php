<?php

namespace App\Providers;

use App\Models\BillingEntry;
use App\Models\CaseEvent;
use App\Models\CaseFlow;
use App\Models\CaseFlowProgress;
use App\Models\CasePermission;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Document;
use App\Models\FirmInvitation;
use App\Models\FlowStep;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\LoginAlertNotification;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    /**
     * Datos de la firma que no se pueden crear, editar ni borrar en modo solo lectura.
     *
     * @var list<class-string<Model>>
     */
    private const READ_ONLY_GUARDED_MODELS = [
        BillingEntry::class,
        CaseEvent::class,
        CaseFlow::class,
        CaseFlowProgress::class,
        CasePermission::class,
        CaseType::class,
        Client::class,
        Document::class,
        FirmInvitation::class,
        FlowStep::class,
        LegalCase::class,
        Reminder::class,
    ];

    public function boot(): void
    {
        $this->guardReadOnlySubscriptions();

        // Registrar fecha y contador de logins para seguimiento de actividad.
        // Cubre tanto login por email/password como Google OAuth (cualquier
        // mecanismo que dispare el evento Auth::login).
        Event::listen(Login::class, function (Login $event) {
            $user = $event->user;
            if (! $user instanceof User) {
                return;
            }

            $isFirstLogin = ! $user->last_login_at;

            $user->forceFill([
                'last_login_at' => now(),
                'login_count' => ($user->login_count ?? 0) + 1,
            ])->saveQuietly();

            // Alerta de seguridad por correo en cada inicio de sesion.
            // Se omite el primer login (es el registro inicial, ya recibe correos
            // de bienvenida) y se respeta la preferencia del usuario.
            if ($isFirstLogin || ! ($user->security_email_enabled ?? true)) {
                return;
            }

            try {
                $user->notify(new LoginAlertNotification(
                    ipAddress: request()->ip() ?? 'desconocida',
                    userAgent: substr((string) request()->userAgent(), 0, 500) ?: 'unknown',
                    loginAt: now()->setTimezone(config('app.timezone'))->format('d/m/Y H:i:s'),
                ));
            } catch (\Throwable $e) {
                Log::warning('No se pudo enviar alerta de login: '.$e->getMessage(), [
                    'user_id' => $user->id,
                ]);
            }
        });
    }

    /**
     * Modo solo lectura por suscripcion vencida.
     *
     * El middleware EnforceSubscriptionStatus no ve las acciones de Filament (van por
     * POST /livewire/update), asi que el bloqueo se hace al guardar o borrar el modelo.
     * Solo aplica a usuarios del panel en peticiones de escritura; cron, colas y el
     * portal del cliente siguen funcionando.
     */
    private function guardReadOnlySubscriptions(): void
    {
        $guard = function (string $event, array $payload): void {
            $model = $payload[0] ?? null;

            if (! $model instanceof Model || ! in_array($model::class, self::READ_ONLY_GUARDED_MODELS, true)) {
                return;
            }

            if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                return;
            }

            $request = request();
            $user = $request->user();

            $isPanelWrite = str_starts_with($request->path(), 'livewire') && ! $request->isMethodSafe();

            if (! $user instanceof User || $user->role === 'superadmin' || ! $user->firm_id
                || ! ($isPanelWrite || app()->runningUnitTests())) {
                return;
            }

            if (! $user->firm?->isReadOnly()) {
                return;
            }

            Notification::make()
                ->title('Cuenta en modo solo lectura')
                ->body('Su suscripcion esta vencida. Renueve el plan en "Mi Plan" para volver a crear o editar registros.')
                ->danger()
                ->persistent()
                ->send();

            throw new Halt;
        };

        Event::listen('eloquent.saving: *', $guard);
        Event::listen('eloquent.deleting: *', $guard);
    }
}
