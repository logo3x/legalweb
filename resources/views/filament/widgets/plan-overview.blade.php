@php
    $user = auth()->user();
    $firm = $user->firm;
    $subscription = $firm?->activeSubscription;
    $plan = $subscription?->plan;
    $planName = $plan?->name ?? 'Gratuito';
    $isFreePlan = !$plan || $plan->slug === 'gratuito';
    $casesUsed = $firm?->realCasesCount() ?? 0;
    $clientsUsed = $firm?->realClientsCount() ?? 0;
    $casesLimit = $plan?->max_cases ?? 5;
    $clientsLimit = $plan?->max_cases ?? 5;
    $casesRemaining = $firm?->casesRemaining() ?? 0;
    $clientsRemaining = $firm?->clientsRemaining() ?? 0;
    $casesPercent = $casesLimit > 0 ? min(100, round(($casesUsed / $casesLimit) * 100)) : 0;
    $clientsPercent = $clientsLimit > 0 ? min(100, round(($clientsUsed / $clientsLimit) * 100)) : 0;
    $isUnlimited = $casesLimit === 0;
    $trialEnds = $subscription?->trial_ends_at;
    $daysLeft = $trialEnds ? now()->diffInDays($trialEnds, false) : null;
@endphp

<x-filament-widgets::widget>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="flex flex-col lg:flex-row">
            {{-- Plan Info --}}
            <div class="flex-1 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $firm?->name ?? 'Mi Firma' }}
                            </h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $isFreePlan ? 'bg-gray-100 text-gray-700' : 'bg-blue-100 text-blue-700' }}">
                                Plan {{ $planName }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">
                            Hola, {{ $user->name }}.
                            @if($daysLeft && $daysLeft > 0)
                                <span class="text-blue-600 font-medium">{{ $daysLeft }} dias de prueba restantes.</span>
                            @endif
                        </p>
                    </div>
                    @if($isFreePlan)
                        <a href="/#planes" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                            Ampliar plan
                        </a>
                    @endif
                </div>

                @unless($isUnlimited)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Casos --}}
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Casos</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $casesUsed }} / {{ $casesLimit }}</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full transition-all {{ $casesPercent >= 80 ? 'bg-red-500' : ($casesPercent >= 60 ? 'bg-yellow-500' : 'bg-blue-600') }}" style="width: {{ $casesPercent }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1.5">
                                @if(is_string($casesRemaining))
                                    {{ $casesRemaining }}
                                @else
                                    {{ $casesRemaining }} disponibles
                                @endif
                            </p>
                        </div>

                        {{-- Clientes --}}
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Clientes</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $clientsUsed }} / {{ $clientsLimit }}</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full transition-all {{ $clientsPercent >= 80 ? 'bg-red-500' : ($clientsPercent >= 60 ? 'bg-yellow-500' : 'bg-green-600') }}" style="width: {{ $clientsPercent }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1.5">
                                @if(is_string($clientsRemaining))
                                    {{ $clientsRemaining }}
                                @else
                                    {{ $clientsRemaining }} disponibles
                                @endif
                            </p>
                        </div>
                    </div>
                @else
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-sm font-medium text-green-700 dark:text-green-400">Plan sin limites. Casos y clientes ilimitados.</span>
                        </div>
                    </div>
                @endunless

                @if($isFreePlan)
                    <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 flex items-start gap-2">
                        <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs text-blue-700 dark:text-blue-400">
                            El plan gratuito incluye 3 casos y 3 clientes. Los datos de ejemplo no cuentan en su limite. <a href="/#planes" target="_blank" class="font-semibold underline">Amplie su plan</a> para desbloquear mas capacidad y notificaciones.
                        </p>
                    </div>
                @endif
            </div>

            {{-- Plan Features --}}
            <div class="border-t lg:border-t-0 lg:border-l border-gray-200 dark:border-gray-700 p-6 lg:w-72 bg-gray-50 dark:bg-gray-900">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Su plan incluye</h4>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $isUnlimited ? 'Casos ilimitados' : "{$casesLimit} casos" }}
                    </li>
                    <li class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ ($plan?->max_users ?? 1) === 0 ? 'Usuarios ilimitados' : ($plan?->max_users ?? 1) . ' usuario(s)' }}
                    </li>
                    <li class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        21 flujos de proceso
                    </li>
                    <li class="flex items-center gap-2 {{ ($plan?->has_portal ?? false) ? 'text-gray-600 dark:text-gray-400' : 'text-gray-400 line-through' }}">
                        <svg class="w-4 h-4 flex-shrink-0 {{ ($plan?->has_portal ?? false) ? 'text-green-500' : 'text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($plan?->has_portal ?? false)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            @endif
                        </svg>
                        Portal del cliente
                    </li>
                    <li class="flex items-center gap-2 {{ ($plan?->has_notifications ?? false) ? 'text-gray-600 dark:text-gray-400' : 'text-gray-400 line-through' }}">
                        <svg class="w-4 h-4 flex-shrink-0 {{ ($plan?->has_notifications ?? false) ? 'text-green-500' : 'text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($plan?->has_notifications ?? false)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            @endif
                        </svg>
                        Notificaciones
                    </li>
                </ul>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
