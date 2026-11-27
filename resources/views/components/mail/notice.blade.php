@props(['tone' => 'info'])
@php
    // Recuadro con fondo tenue y texto del mismo tono: el color solo señala urgencia o resultado.
    [$ink, $bg, $line] = match ($tone) {
        'danger' => ['#991B1B', '#FEF2F2', '#FECACA'],
        'warning' => ['#92400E', '#FFFBEB', '#FDE68A'],
        'success' => ['#065F46', '#ECFDF5', '#A7F3D0'],
        'neutral' => ['#334155', '#F8FAFC', '#E2E8F0'],
        default => ['#1E3A8A', '#EFF6FF', '#BFDBFE'],
    };
@endphp
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:0 0 20px;">
  <tr><td class="lw-md" style="padding:14px 16px;background:{{ $bg }};border:1px solid {{ $line }};border-radius:8px;font-size:15px;line-height:1.55;color:{{ $ink }};">
    {{ $slot }}
  </td></tr>
</table>
