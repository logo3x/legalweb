<?php

namespace App\Providers;

use App\Models\User;
use App\Notifications\LoginAlertNotification;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
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
}
