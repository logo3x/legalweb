@props(['items' => []])
{{--
    Datos del expediente en filas (etiqueta / valor) separadas por lineas finas.
    items: lista de ['label' => string, 'value' => string, 'mono' => bool, 'plain' => bool, 'tone' => 'danger'|null]; los vacios se omiten.
--}}
@php
    $rows = collect($items)->filter(fn ($item) => filled($item['value'] ?? null))->values();
@endphp
@if($rows->isNotEmpty())
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:4px 0 8px;border-top:1px solid #E2E8F0;">
  @foreach($rows as $item)
    <tr>
      <td class="lw-fact-label" width="34%" style="padding:11px 12px 11px 0;border-bottom:1px solid #E2E8F0;vertical-align:top;font-size:14px;line-height:1.45;color:#475569;">{{ $item['label'] }}</td>
      <td class="lw-fact-value" style="padding:11px 0;border-bottom:1px solid #E2E8F0;vertical-align:top;font-size:15px;line-height:1.45;color:{{ ($item['tone'] ?? null) === 'danger' ? '#B91C1C' : (! empty($item['plain']) ? '#334155' : '#0F172A') }};font-weight:{{ ($item['tone'] ?? null) === 'danger' ? '700' : (! empty($item['plain']) ? '400' : '500') }};{{ ! empty($item['mono']) ? "font-family:'SFMono-Regular',Menlo,Consolas,monospace;font-size:14px;" : '' }}">{{ $item['value'] }}</td>
    </tr>
  @endforeach
</table>
@endif
