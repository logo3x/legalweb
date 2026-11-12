<?php

namespace App\Services;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Limites de frecuencia para acciones costosas o que consultan servicios externos:
 * IA (cuota gratuita compartida por todas las firmas), WhatsApp (Twilio cobra por mensaje)
 * y consultas a la Rama Judicial o SAMAI (pueden bloquear la IP del servidor).
 */
class UsageLimiter
{
    /**
     * Intentos permitidos y ventana en segundos por tipo de accion.
     *
     * @var array<string, array{0: int, 1: int}>
     */
    public const LIMITS = [
        'ai' => [30, 3600],
        'judicial' => [20, 60],
        'whatsapp-portal' => [3, 3600],
        'test-notification' => [10, 3600],
    ];

    /**
     * Registra un intento; si se supera el limite avisa al usuario y devuelve false.
     */
    public static function attempt(string $bucket, string|int|null $scope = null): bool
    {
        [$maxAttempts, $decaySeconds] = self::LIMITS[$bucket];
        $key = $bucket.':'.($scope ?? auth()->id());

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

            Notification::make()
                ->title('Limite de uso alcanzado')
                ->body("Hizo demasiadas solicitudes seguidas. Intente de nuevo en {$minutes} minuto(s).")
                ->warning()
                ->send();

            return false;
        }

        RateLimiter::hit($key, $decaySeconds);

        return true;
    }
}
