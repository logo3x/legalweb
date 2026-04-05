<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Firm;
use App\Models\FirmInvitation;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\DemoDataService;
use Illuminate\Support\Facades\Auth;
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
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]);

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
            'permissions' => $invitation->permissions,
            'password' => bcrypt(str()->random(32)),
        ]);

        $invitation->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        Auth::login($user, remember: true);

        return redirect('/admin');
    }

    private function createNewFirm($googleUser)
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
                'trial_ends_at' => now()->addDays(30),
            ]);
        }

        $user = User::create([
            'name' => $googleUser->getName(),
            'email' => strtolower($googleUser->getEmail()),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'firm_id' => $firm->id,
            'role' => 'admin',
            'password' => bcrypt(str()->random(32)),
        ]);

        app(DemoDataService::class)->seedForFirm($firm, $user);

        Auth::login($user, remember: true);

        return redirect('/admin/onboarding');
    }
}
