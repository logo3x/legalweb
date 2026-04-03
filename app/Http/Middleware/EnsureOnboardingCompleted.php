<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->firm) {
            return $next($request);
        }

        if (! $user->firm->onboarding_completed) {
            $firmSettingsUrl = '/admin/firm-settings';

            if (! $request->is('admin/firm-settings*') && ! $request->is('livewire/*')) {
                return redirect($firmSettingsUrl);
            }
        }

        return $next($request);
    }
}
