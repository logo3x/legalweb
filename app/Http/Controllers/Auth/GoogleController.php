<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Firm;
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

        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            Auth::login($user, remember: true);

            return redirect('/admin');
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]);

            Auth::login($user, remember: true);

            return redirect('/admin');
        }

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
            'email' => $googleUser->getEmail(),
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
