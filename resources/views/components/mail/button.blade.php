@props(['href', 'secondary' => false])
@php
    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
@endphp
{{-- Boton "a prueba de Outlook": tabla con fondo solido, sin sombras ni degradados. --}}
<table cellpadding="0" cellspacing="0" role="presentation" style="margin:26px 0 22px;">
  <tr>
    @if($secondary)
      <td style="border:1px solid #CBD5E1;border-radius:8px;">
        <a class="lw-btn" href="{{ $href }}" target="_blank" style="display:inline-block;padding:12px 22px;font-family:{{ $font }};font-size:15px;font-weight:600;line-height:1.2;color:#1E3A5F;text-decoration:none;border-radius:8px;">{{ $slot }}</a>
      </td>
    @else
      <td bgcolor="#1E3A5F" style="background:#1E3A5F;border-radius:8px;">
        <a class="lw-btn" href="{{ $href }}" target="_blank" style="display:inline-block;padding:13px 24px;font-family:{{ $font }};font-size:15px;font-weight:600;line-height:1.2;color:#FFFFFF;text-decoration:none;border-radius:8px;background:#1E3A5F;">{{ $slot }}</a>
      </td>
    @endif
  </tr>
</table>
