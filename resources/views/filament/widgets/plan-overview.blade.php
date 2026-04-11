@php
    $user = auth()->user();
    $firm = $user->firm;
    $subscription = $firm?->activeSubscription;
    $plan = $subscription?->plan;
    $planName = $plan?->name ?? 'Gratuito';
    $isFreePlan = !$plan || $plan->slug === 'gratuito';
    $casesUsed = $firm?->realCasesCount() ?? 0;

    // Plan gratuito: mostrar countdown de trial
    // Plan pago: mostrar casos usados vs limite
    if ($isFreePlan) {
        $trialEnds = $subscription?->trial_ends_at ?? now()->addMonths(3);
        $daysLeft = max(0, (int) now()->startOfDay()->diffInDays($trialEnds->startOfDay(), false));
        $trialExpired = $daysLeft <= 0;
        $totalTrialDays = 90;
        $trialPercent = min(100, max(0, round((($totalTrialDays - $daysLeft) / $totalTrialDays) * 100)));
        $barColor = $daysLeft <= 7 ? '#ef4444' : ($daysLeft <= 30 ? '#f59e0b' : '#10b981');
    } else {
        $casesLimit = $plan?->max_cases ?? 999;
        $casesPercent = $casesLimit > 0 ? min(100, round(($casesUsed / $casesLimit) * 100)) : 0;
        $casesRemaining = max(0, $casesLimit - $casesUsed);
        $barColor = $casesPercent >= 80 ? '#ef4444' : ($casesPercent >= 50 ? '#f59e0b' : '#10b981');
    }

    $hasPortal = $plan?->has_portal ?? true;
    $hasNotif = $plan?->has_notifications ?? false;
    $maxUsers = $plan?->max_users ?? 1;
@endphp

<x-filament-widgets::widget>
    <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px 24px; font-family: Inter, system-ui, sans-serif;">
        {{-- Header --}}
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: {{ $isFreePlan ? '#fef3c7' : '#dbeafe' }}; display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" fill="none" stroke="{{ $isFreePlan ? '#d97706' : '#3b82f6' }}" viewBox="0 0 24 24" style="min-width:20px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size: 16px; font-weight: 700; color: #111827;">Plan {{ $planName }}</div>
                    @if($isFreePlan)
                        @if($trialExpired)
                            <div style="font-size: 13px; color: #ef4444; font-weight: 600;">Periodo de prueba finalizado</div>
                        @else
                            <div style="font-size: 13px; color: #6b7280;">{{ $daysLeft }} dias restantes de prueba &middot; {{ $casesUsed }} casos creados</div>
                        @endif
                    @else
                        <div style="font-size: 13px; color: #6b7280;">{{ $casesRemaining }} casos disponibles de {{ $casesLimit }}</div>
                    @endif
                </div>
            </div>

            @if($isFreePlan)
                <a href="/admin/planes" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; background: linear-gradient(135deg, #f59e0b, #ea580c); color: #fff; font-size: 13px; font-weight: 600; border-radius: 8px; text-decoration: none; white-space: nowrap;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="min-width:16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Mejorar plan
                </a>
            @endif
        </div>

        {{-- Progress bar --}}
        @if($isFreePlan)
            <div style="width: 100%; height: 10px; background: #f3f4f6; border-radius: 999px; margin-bottom: 8px; overflow: hidden;">
                <div style="width: {{ max(3, $trialPercent) }}%; height: 100%; background: {{ $barColor }}; border-radius: 999px; transition: width 0.5s;"></div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 16px;">
                <span style="font-size: 13px; color: #374151;">
                    @if($trialExpired)
                        <strong style="color: #ef4444;">Vencido</strong>
                    @elseif($daysLeft <= 7)
                        <strong style="color: #ef4444;">{{ $daysLeft }} dias</strong> restantes
                    @elseif($daysLeft <= 30)
                        <strong style="color: #f59e0b;">{{ $daysLeft }} dias</strong> restantes
                    @else
                        <strong>{{ $daysLeft }} dias</strong> restantes
                    @endif
                </span>
                <span style="font-size: 13px; color: #374151;">Vence: {{ $trialEnds->format('d/m/Y') }}</span>
            </div>
        @else
            <div style="width: 100%; height: 10px; background: #f3f4f6; border-radius: 999px; margin-bottom: 8px; overflow: hidden;">
                <div style="width: {{ max(3, $casesPercent) }}%; height: 100%; background: {{ $barColor }}; border-radius: 999px; transition: width 0.5s;"></div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 16px;">
                <span style="font-size: 13px; color: #374151;"><strong>{{ $casesUsed }}</strong> casos usados</span>
                <span style="font-size: 13px; color: #374151;"><strong>{{ $casesLimit }}</strong> limite del plan</span>
            </div>
        @endif

        {{-- Features badges --}}
        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 12px 16px; background: {{ $isFreePlan ? ($trialExpired ? '#fef2f2' : '#f0fdf4') : '#f0fdf4' }}; border-radius: 8px;">
            @if($isFreePlan)
                @if($trialExpired)
                    <span style="font-size: 13px; color: #991b1b; font-weight: 500;">
                        Su periodo de prueba ha finalizado. Para seguir usando LegalWeb, elija un plan.
                    </span>
                    <a href="/admin/planes" style="font-size: 12px; font-weight: 600; color: #3b82f6; text-decoration: none; margin-left: 4px;">
                        Ver planes &rarr;
                    </a>
                @else
                    @php
                        $features = [
                            ['label' => 'Casos ilimitados', 'active' => true],
                            ['label' => $maxUsers . ' usuario(s)', 'active' => true],
                            ['label' => 'Portal cliente', 'active' => true],
                            ['label' => 'Asistente IA', 'active' => true],
                            ['label' => '21 flujos', 'active' => true],
                            ['label' => 'Rama Judicial', 'active' => true],
                        ];
                    @endphp
                    @foreach($features as $feature)
                        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; background: #dcfce7; color: #166534;">
                            <svg width="14" height="14" fill="none" stroke="#16a34a" viewBox="0 0 24 24" style="min-width:14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ $feature['label'] }}
                        </span>
                    @endforeach
                    <a href="/admin/planes" style="font-size: 12px; font-weight: 600; color: #3b82f6; text-decoration: none; margin-left: 4px;">
                        Ver planes &rarr;
                    </a>
                @endif
            @else
                @php
                    $features = [
                        ['label' => $casesLimit . ' casos', 'active' => true],
                        ['label' => $maxUsers . ' usuario(s)', 'active' => true],
                        ['label' => 'Portal cliente', 'active' => $hasPortal],
                        ['label' => 'Notificaciones', 'active' => $hasNotif],
                        ['label' => '21 flujos', 'active' => true],
                        ['label' => 'Rama Judicial', 'active' => true],
                    ];
                @endphp
                @foreach($features as $feature)
                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; {{ $feature['active'] ? 'background: #dcfce7; color: #166534;' : 'background: #f3f4f6; color: #9ca3af; text-decoration: line-through;' }}">
                        @if($feature['active'])
                            <svg width="14" height="14" fill="none" stroke="#16a34a" viewBox="0 0 24 24" style="min-width:14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <svg width="14" height="14" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" style="min-width:14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        @endif
                        {{ $feature['label'] }}
                    </span>
                @endforeach
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
