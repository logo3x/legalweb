@php
    $firm = auth()->user()->firm;
    $limits = $firm?->planLimits();
    $tier = $limits?->tier() ?? 'unmanaged';
    $planName = $limits?->label() ?? 'Sin plan';
    $showUpgrade = in_array($tier, ['trial', 'free'], true);

    $activeCases = $limits?->activeCasesCount() ?? 0;
    $maxCases = $limits?->maxActiveCases();
    $maxUsers = $limits?->maxUsers();
    $seats = $limits?->seatsUsed() ?? 1;
    $aiLimit = $limits?->aiMonthlyLimit();
    $aiUsed = $aiLimit !== null ? $limits->aiUsedThisMonth() : 0;

    if ($tier === 'trial') {
        $limitsStart = $limits->limitsStartAt();
        $daysLeft = max(0, (int) now()->startOfDay()->diffInDays($limitsStart->copy()->startOfDay(), false));
        $percent = min(100, max(3, round(((90 - min(90, $daysLeft)) / 90) * 100)));
        $barColor = $daysLeft <= 7 ? '#ef4444' : ($daysLeft <= 30 ? '#f59e0b' : '#10b981');
        $subtitle = "{$daysLeft} días de prueba con todo incluido";
        $leftNote = "<strong>{$daysLeft} días</strong> restantes";
        $rightNote = 'Desde el '.$limitsStart->format('d/m/Y').': plan Gratuito';
    } elseif ($maxCases !== null) {
        $percent = min(100, max(3, round(($activeCases / max(1, $maxCases)) * 100)));
        $barColor = $percent >= 100 ? '#ef4444' : ($percent >= 80 ? '#f59e0b' : '#10b981');
        $subtitle = "{$activeCases} de {$maxCases} casos activos";
        $leftNote = "<strong>{$activeCases}</strong> casos activos";
        $rightNote = "Límite: <strong>{$maxCases}</strong>";
    } else {
        $percent = null;
        $subtitle = 'Casos ilimitados'.($maxUsers ? " · {$seats} de {$maxUsers} usuarios" : '');
    }

    $features = [
        ['label' => $maxCases === null ? 'Casos ilimitados' : "{$maxCases} casos activos", 'active' => true],
        ['label' => ($maxUsers ?? 'Usuarios').($maxUsers ? ' usuario(s)' : ' ilimitados'), 'active' => true],
        ['label' => $aiLimit === null ? 'Asistente IA' : "IA: {$aiUsed}/{$aiLimit} este mes", 'active' => true],
        ['label' => 'Avisos por WhatsApp', 'active' => $limits?->hasWhatsApp() ?? true],
        ['label' => 'Vigilancia de clientes', 'active' => $limits?->hasClientMonitoring() ?? true],
        ['label' => ($limits?->syncEveryDays() ?? 1) > 1 ? 'Rama Judicial semanal' : 'Rama Judicial diaria', 'active' => true],
    ];
@endphp

<x-filament-widgets::widget>
    <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px 24px; font-family: Inter, system-ui, sans-serif;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: {{ $showUpgrade ? '#fef3c7' : '#dbeafe' }}; display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" fill="none" stroke="{{ $showUpgrade ? '#d97706' : '#3b82f6' }}" viewBox="0 0 24 24" style="min-width:20px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size: 16px; font-weight: 700; color: #111827;">Plan {{ $planName }}</div>
                    <div style="font-size: 13px; color: #6b7280;">{{ $subtitle }}</div>
                </div>
            </div>

            @if($showUpgrade)
                <a href="/admin/planes" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; background: linear-gradient(135deg, #f59e0b, #ea580c); color: #fff; font-size: 13px; font-weight: 600; border-radius: 8px; text-decoration: none; white-space: nowrap;">
                    Mejorar plan
                </a>
            @endif
        </div>

        @if($percent !== null)
            <div style="width: 100%; height: 10px; background: #f3f4f6; border-radius: 999px; margin-bottom: 8px; overflow: hidden;">
                <div style="width: {{ $percent }}%; height: 100%; background: {{ $barColor }}; border-radius: 999px;"></div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 13px; color: #374151;">
                <span>{!! $leftNote !!}</span>
                <span>{!! $rightNote !!}</span>
            </div>
        @endif

        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 12px 16px; background: #f8fafc; border-radius: 8px;">
            @foreach($features as $feature)
                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; {{ $feature['active'] ? 'background: #dcfce7; color: #166534;' : 'background: #f3f4f6; color: #9ca3af; text-decoration: line-through;' }}">
                    {{ $feature['label'] }}
                </span>
            @endforeach
            @if($showUpgrade)
                <a href="/admin/planes" style="font-size: 12px; font-weight: 600; color: #3b82f6; text-decoration: none; margin-left: 4px;">Ver planes &rarr;</a>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
