{{--
    Avisos de cuenta, plan y suscripcion.
    Variables: heading, eyebrow (opcional), intro (HTML del codigo), lines (HTML), highlight (HTML),
    actionUrl, actionLabel, tone ('info' | 'warning' | 'danger' | 'success'), footerNote (HTML).
    intro, lines, highlight y footerNote se escriben en el codigo (no traen datos de usuarios sin escapar).
--}}
@php
    $tone = $tone ?? 'info';
    $eyebrowColor = match ($tone) {
        'danger' => '#B91C1C',
        'warning' => '#B45309',
        'success' => '#047857',
        default => '#475569',
    };
    $lines = $lines ?? [];
@endphp
<x-mail.layout :title="$heading ?? null" :preheader="filled($intro ?? null) ? \Illuminate\Support\Str::limit(strip_tags($intro), 110) : null">
    @if(filled($eyebrow ?? null))
        <p style="margin:0 0 6px;font-size:14px;font-weight:600;color:{{ $eyebrowColor }};">{{ $eyebrow }}</p>
    @endif

    <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;font-weight:700;color:#0F172A;letter-spacing:-0.01em;">{{ $heading ?? '' }}</h1>

    @if(filled($intro ?? null))
        <p style="margin:0 0 14px;color:#334155;">{!! $intro !!}</p>
    @endif

    @foreach($lines as $line)
        <p style="margin:0 0 14px;color:#334155;">{!! $line !!}</p>
    @endforeach

    @if(filled($highlight ?? null))
        <x-mail.notice :tone="$tone === 'info' ? 'neutral' : $tone">{!! $highlight !!}</x-mail.notice>
    @endif

    @if(filled($actionUrl ?? null) && filled($actionLabel ?? null))
        <x-mail.button :href="$actionUrl">{{ $actionLabel }}</x-mail.button>
    @endif

    @if(filled($footerNote ?? null))
        <p style="margin:0;font-size:14px;line-height:1.6;color:#475569;">{!! $footerNote !!}</p>
    @endif
</x-mail.layout>
