<?php

namespace App\Console\Commands;

use App\Models\LegalCase;
use App\Models\MassEmailCampaign;
use App\Models\MassEmailTemplate;
use App\Models\User;
use App\Notifications\MassEmailNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Signature('app:send-lifecycle-emails {--limit=100}')]
#[Description('Envia los correos automaticos de ciclo de vida: bienvenida, primer proceso e inactividad')]
class SendLifecycleEmails extends Command
{
    /**
     * Clave => plantilla de Correos masivos que se envia.
     */
    public const TEMPLATES = [
        'bienvenida' => 'Bienvenida - primeros pasos',
        'primer_proceso' => 'Primer proceso - dia 3',
        'inactivo_7d' => 'Inactivo 7 dias - retomar pronto',
        'inactivo_30d' => 'Inactivo 30 dias - lo extranamos',
    ];

    /**
     * Correos que se vuelven a enviar si el usuario entra y luego vuelve a dejar de entrar.
     */
    private const REARMABLE = ['inactivo_7d', 'inactivo_30d'];

    public function handle(): int
    {
        $templates = MassEmailTemplate::whereIn('name', self::TEMPLATES)->where('is_active', true)->get()->keyBy('name');
        $sent = 0;
        $limit = max(1, (int) $this->option('limit'));

        $users = User::with('firm')
            ->whereNotNull('firm_id')
            ->where('role', '!=', 'superadmin')
            ->whereNull('mass_email_opt_out_at')
            ->where('email', 'not like', '%@example.com')
            ->where('created_at', '>=', now()->subDays(120))
            ->get();

        foreach ($users as $user) {
            if ($sent >= $limit) {
                break;
            }

            $key = $this->dueEmail($user);
            $template = $key ? $templates->get(self::TEMPLATES[$key]) : null;

            if (! $key || ! $template) {
                continue;
            }

            try {
                $campaign = new MassEmailCampaign(['subject' => $template->subject, 'body' => $template->body]);
                $user->notifyNow(new MassEmailNotification($campaign));

                DB::table('lifecycle_emails')->updateOrInsert(['user_id' => $user->id, 'key' => $key], ['sent_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Correo de ciclo de vida fallo: '.$e->getMessage(), ['user_id' => $user->id, 'key' => $key]);
            }
        }

        $this->info("Correos de ciclo de vida enviados: {$sent}");

        return self::SUCCESS;
    }

    /**
     * El correo que le corresponde hoy al usuario (como maximo uno por dia).
     */
    public function dueEmail(User $user): ?string
    {
        $sentAt = DB::table('lifecycle_emails')->where('user_id', $user->id)->pluck('sent_at', 'key')
            ->map(fn ($date): Carbon => Carbon::parse($date));

        // Uno por dia como maximo, para no saturar.
        if ($sentAt->contains(fn (Carbon $date): bool => $date->gt(now()->subDay()))) {
            return null;
        }

        $registeredDays = (int) $user->created_at->diffInDays(now());
        $lastLogin = $user->last_login_at ? Carbon::parse($user->last_login_at) : $user->created_at;
        $inactiveDays = (int) $lastLogin->diffInDays(now());

        $wasSent = function (string $key) use ($sentAt, $lastLogin): bool {
            if (! $sentAt->has($key)) {
                return false;
            }

            // Inactividad: si volvio a entrar despues del aviso, se puede avisar de nuevo.
            return ! in_array($key, self::REARMABLE, true) || $sentAt[$key]->gt($lastLogin);
        };

        if ($user->isAdmin() && $registeredDays <= 2 && ! $wasSent('bienvenida')) {
            return 'bienvenida';
        }

        if ($user->isAdmin() && $registeredDays >= 3 && $registeredDays <= 30 && ! $wasSent('primer_proceso') && ! $this->firmHasRealProcess($user)) {
            return 'primer_proceso';
        }

        if ($inactiveDays >= 30 && $inactiveDays < 90 && ! $wasSent('inactivo_30d')) {
            return 'inactivo_30d';
        }

        if ($inactiveDays >= 7 && $inactiveDays < 30 && ! $wasSent('inactivo_7d')) {
            return 'inactivo_7d';
        }

        return null;
    }

    private function firmHasRealProcess(User $user): bool
    {
        return LegalCase::withoutGlobalScopes()
            ->where('firm_id', $user->firm_id)
            ->whereNull('deleted_at')
            ->where('is_demo', false)
            ->whereNotNull('external_case_number')
            ->where('external_case_number', '!=', '')
            ->exists();
    }
}
