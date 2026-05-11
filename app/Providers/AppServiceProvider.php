<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
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
            if ($user instanceof User) {
                $user->forceFill([
                    'last_login_at' => now(),
                    'login_count' => ($user->login_count ?? 0) + 1,
                ])->saveQuietly();
            }
        });
    }
}
