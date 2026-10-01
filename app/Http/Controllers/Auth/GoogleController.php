<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CasePermission;
use App\Models\Firm;
use App\Models\FirmInvitation;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\NewFirmRegistered;
use App\Services\DemoDataService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();
        $email = strtolower($googleUser->getEmail());

        // Usuario existente con Google ID
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            Auth::login($user, remember: true);

            return redirect('/admin');
        }

        // Usuario existente con email
        $user = User::where('email', $email)->first();

        if ($user) {
            $attributes = [
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ];

            // Google acaba de probar que el correo es de esta persona. Si la cuenta nunca se
            // verifico, pudo crearla otro con ese correo: se invalida su contrasena.
            if (! $user->email_verified_at) {
                $attributes['password'] = bcrypt(str()->random(32));
                $attributes['email_verified_at'] = now();
            }

            $user->forceFill($attributes)->save();

            if (! $user->firm_id && $user->role !== 'superadmin') {
                return $this->createNewFirm($googleUser, $user);
            }

            Auth::login($user, remember: true);

            return redirect('/admin');
        }

        // Verificar si tiene invitacion pendiente
        $invitation = FirmInvitation::where('email', $email)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($invitation) {
            return $this->acceptInvitation($invitation, $googleUser);
        }

        // Nuevo usuario — crear firma
        return $this->createNewFirm($googleUser);
    }

    private function acceptInvitation(FirmInvitation $invitation, $googleUser)
    {
        $user = User::create([
            'name' => $googleUser->getName(),
            'email' => strtolower($googleUser->getEmail()),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'firm_id' => $invitation->firm_id,
            'role' => $invitation->role,
            'password' => bcrypt(str()->random(32)),
        ]);

        // Crear permisos por caso pre-asignados
        $invitationData = $invitation->permissions ?? [];
        $caseIds = $invitationData['case_ids'] ?? [];
        $casePermissions = $invitationData['case_permissions'] ?? [];

        foreach ($caseIds as $caseId) {
            CasePermission::create([
                'user_id' => $user->id,
                'legal_case_id' => $caseId,
                'permissions' => $casePermissions,
                'assigned_by' => $invitation->invited_by,
            ]);
        }

        $invitation->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        Auth::login($user, remember: true);

        return redirect('/admin');
    }

    /**
     * Crea la firma del usuario. Si ya existe una cuenta sin firma, la convierte en admin de la nueva.
     */
    private function createNewFirm($googleUser, ?User $existingUser = null)
    {
        $firm = Firm::create([
            'name' => 'Mi Firma Legal',
        ]);

        $freePlan = Plan::where('slug', 'gratuito')->first();

        if ($freePlan) {
            Subscription::create([
                'firm_id' => $firm->id,
                'plan_id' => $freePlan->id,
                'status' => 'active',
                'starts_at' => now(),
                'trial_ends_at' => now()->addMonths(3),
            ]);
        }

        if ($existingUser) {
            $existingUser->update(['firm_id' => $firm->id, 'role' => 'admin']);
            $user = $existingUser;
        } else {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => strtolower($googleUser->getEmail()),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'firm_id' => $firm->id,
                'role' => 'admin',
                'password' => bcrypt(str()->random(32)),
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        app(DemoDataService::class)->seedForFirm($firm, $user);

        // Notificar a superadmins y emails configurados
        $this->notifyNewFirmRegistered($firm, $user);

        Auth::login($user, remember: true);

        return redirect('/admin/onboarding');
    }

    /**
     * Enviar notificacion a superadmins y emails configurados cuando se registra una nueva firma.
     */
    private function notifyNewFirmRegistered(Firm $firm, User $user): void
    {
        try {
            $notification = new NewFirmRegistered($firm, $user);

            // Notificar a todos los superadmins
            User::where('role', 'superadmin')->each(function ($superadmin) use ($notification, $user) {
                if ($superadmin->email && $superadmin->id !== $user->id) {
                    $superadmin->notify($notification);
                }
            });

            // Notificar a emails adicionales configurados en .env
            $extraEmails = array_filter(array_map('trim', explode(',', config('services.notifications.new_firm_emails', ''))));
            foreach ($extraEmails as $email) {
                Notification::route('mail', $email)->notify($notification);
            }
        } catch (\Exception $e) {
            Log::warning('Error enviando notificacion nueva firma: '.$e->getMessage());
        }
    }
}
