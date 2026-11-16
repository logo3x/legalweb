<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SystemNewsNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Envia novedades del sistema a los superadmin (correo + campana).
 */
class SystemNews
{
    /**
     * @param  list<string>  $lines
     * @param  string|null  $onceKey  si se indica, la misma novedad no se repite durante $onceForDays dias
     */
    public static function notifySuperadmins(string $title, array $lines, string $actionLabel = 'Abrir el panel', string $actionUrl = '', ?string $onceKey = null, int $onceForDays = 30): bool
    {
        if ($onceKey !== null && ! Cache::add('system_news.'.$onceKey, true, now()->addDays($onceForDays))) {
            return false;
        }

        $superadmins = User::where('role', 'superadmin')->get();

        if ($superadmins->isEmpty()) {
            return false;
        }

        try {
            Notification::send($superadmins, new SystemNewsNotification($title, $lines, $actionLabel, $actionUrl));
        } catch (\Throwable $e) {
            Log::warning('SystemNews: no se pudo enviar la novedad', ['title' => $title, 'error' => $e->getMessage()]);

            return false;
        }

        return true;
    }
}
