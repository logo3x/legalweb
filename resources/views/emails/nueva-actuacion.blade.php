{{--
    Aviso de actuaciones nuevas (o datos cambiados) en un proceso vigilado.
    Variables: lawyerName, newCount, eventTitle, eventType, eventDate, radicado, despacho, clientName,
    caseTitle, caseUrl, fieldChanges.
--}}
@php
    $types = ['actuacion' => 'Actuación', 'audiencia' => 'Audiencia', 'notificacion' => 'Notificación', 'memorial' => 'Memorial', 'auto' => 'Auto', 'sentencia' => 'Sentencia'];
    $typeLabel = $types[$eventType ?? 'actuacion'] ?? ucfirst((string) ($eventType ?? 'Actuación'));
    $count = (int) ($newCount ?? 0);
    $summary = match (true) {
        $count > 1 => "{$count} actuaciones nuevas",
        $count === 1 => 'Una actuación nueva',
        default => 'Cambiaron datos del proceso',
    };
    $changes = $fieldChanges ?? [];
@endphp
<x-mail.layout :title="$summary.' · '.($radicado ?? '')" :preheader="$summary.' en el radicado '.($radicado ?? '').': '.($eventTitle ?? '')">
    <p style="margin:0 0 6px;font-size:14px;color:#475569;">{{ $summary }} en la Rama Judicial</p>
    <h1 style="margin:0 0 6px;font-size:22px;line-height:1.3;font-weight:700;color:#0F172A;letter-spacing:-0.01em;">{{ $eventTitle ?? 'Nueva actuación' }}</h1>
    @if($count > 0)
        <p style="margin:0 0 22px;font-size:14px;color:#475569;">{{ $typeLabel }}@if(filled($eventDate ?? null)) &middot; {{ $eventDate }}@endif</p>
    @else
        <div style="height:16px;line-height:16px;font-size:0;">&nbsp;</div>
    @endif

    <x-mail.facts :items="[
        ['label' => 'Radicado', 'value' => $radicado ?? null, 'mono' => true],
        ['label' => 'Despacho', 'value' => ($despacho ?? '-') !== '-' ? $despacho : null],
        ['label' => 'Cliente', 'value' => ($clientName ?? '-') !== '-' ? $clientName : null],
        ['label' => 'Caso', 'value' => $caseTitle ?? null],
    ]" />

    @if(! empty($changes))
        <p style="margin:22px 0 8px;font-size:15px;font-weight:700;color:#0F172A;">Datos del proceso que cambiaron</p>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-top:1px solid #E2E8F0;">
            @foreach($changes as $change)
                <tr><td style="padding:10px 0;border-bottom:1px solid #E2E8F0;font-size:14px;line-height:1.5;color:#334155;">
                    <strong style="color:#0F172A;">{{ $change['campo'] }}</strong><br>
                    <span style="color:#475569;text-decoration:line-through;">{{ $change['antes'] }}</span>
                    &nbsp;&rarr;&nbsp;
                    <span style="color:#0F172A;font-weight:600;">{{ $change['despues'] }}</span>
                </td></tr>
            @endforeach
        </table>
    @endif

    <x-mail.button :href="$caseUrl">Ver el caso</x-mail.button>

    <p style="margin:0;font-size:14px;line-height:1.6;color:#475569;">
        {{ $lawyerName }}, seguimos consultando este proceso todos los días. Si una consulta a la Rama falla, también se lo avisamos.
    </p>
</x-mail.layout>
