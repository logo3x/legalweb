{{--
    Aviso de un recordatorio o termino que vence. Lo urgente (cuando vence) va primero y en rojo;
    el resto son los datos del expediente.
    Variables: lawyerName, pillText, headline, title, dueAt, priorityLabel, caseNumber, despacho,
    description, ctaUrl, ctaLabel.
--}}
@php
    $status = html_entity_decode((string) ($pillText ?? 'Vence pronto'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $headlineText = html_entity_decode((string) ($headline ?? 'Tiene un término por vencer'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $statusLower = mb_strtolower($status);
    $isUrgent = str_contains($statusLower, 'vencid') || str_contains($statusLower, 'hoy') || str_contains($statusLower, 'mañana');
@endphp
<x-mail.layout :title="$status.' · '.($title ?? '')" :preheader="$status.': '.($title ?? '')">
    <x-mail.notice :tone="$isUrgent ? 'danger' : 'warning'">
        <strong>{{ $status }}</strong>@if(filled($dueAt ?? null)) &middot; {{ $dueAt }}@endif
    </x-mail.notice>

    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;font-weight:700;color:#0F172A;letter-spacing:-0.01em;">{{ $title }}</h1>

    <p style="margin:0 0 20px;color:#334155;">
        {{ $lawyerName }}: {{ \Illuminate\Support\Str::finish($headlineText, '.') }}
        Los plazos se cuentan en días hábiles según el calendario judicial.
    </p>

    <x-mail.facts :items="[
        ['label' => 'Fecha límite', 'value' => $dueAt ?? null, 'tone' => 'danger'],
        ['label' => 'Prioridad', 'value' => $priorityLabel ?? null],
        ['label' => 'Caso', 'value' => $caseNumber ?? null, 'mono' => true],
        ['label' => 'Despacho', 'value' => $despacho ?? null],
        ['label' => 'Detalle', 'value' => $description ?? null, 'plain' => true],
    ]" />

    <x-mail.button :href="$ctaUrl">{{ $ctaLabel ?? 'Ver en la agenda' }}</x-mail.button>

    <p style="margin:0;font-size:14px;line-height:1.6;color:#475569;">
        Cuando lo atienda, márquelo como completado en la agenda y dejaremos de recordárselo.
    </p>
</x-mail.layout>
