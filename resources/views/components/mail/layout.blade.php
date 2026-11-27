@props([
    'title' => null,
    'preheader' => null,
    'firmLogo' => null,
    'firmName' => null,
    'audience' => 'account',
    'unsubscribeUrl' => null,
])
@php
    /*
     * Marco comun de todos los correos de LegalWeb: una carta sobria.
     * Nombre en texto (no depende de imagenes bloqueadas), color solo para lo urgente,
     * estilos en linea porque los clientes de correo ignoran casi todo el CSS externo.
     *
     * audience: 'account' (usuarios de LegalWeb), 'client' (clientes de una firma), 'campaign' (boletines).
     */
    $appName = config('app.name', 'LegalWeb');
    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
    $hasFirmLogo = filled($firmLogo);
    $senderName = filled($firmName) ? $firmName : $appName;
    $from = config('mail.from.address');
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title>{{ $title ?? $appName }}</title>
<!--[if mso]>
<style type="text/css">body, table, td, p, a, h1 { font-family: Arial, sans-serif !important; }</style>
<![endif]-->
<style>
  a { color: #1D4ED8; }
  .lw-md p { margin: 0 0 12px; }
  .lw-md p:last-child { margin-bottom: 0; }
  .lw-md ul, .lw-md ol { margin: 0 0 12px; padding-left: 22px; }
  @media only screen and (max-width: 600px) {
    .lw-container { width: 100% !important; }
    .lw-pad { padding-left: 22px !important; padding-right: 22px !important; }
    .lw-btn { display: block !important; text-align: center !important; }
    .lw-fact-label, .lw-fact-value { display: block !important; width: 100% !important; }
    .lw-fact-label { padding-bottom: 0 !important; border-bottom: 0 !important; }
    .lw-fact-value { padding-top: 2px !important; }
  }
  {{ $styles ?? '' }}
</style>
</head>
<body style="margin:0;padding:0;background:#F1F5F9;font-family:{{ $font }};color:#334155;-webkit-font-smoothing:antialiased;">

@if(filled($preheader))
<div style="display:none;font-size:1px;color:#F1F5F9;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ $preheader }}</div>
@endif

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#F1F5F9;">
<tr><td align="center" style="padding:32px 12px;">

  <table class="lw-container" width="600" cellpadding="0" cellspacing="0" role="presentation" style="width:600px;max-width:600px;background:#FFFFFF;border:1px solid #E2E8F0;border-radius:12px;">

    {{-- Membrete --}}
    <tr><td class="lw-pad" style="padding:26px 40px 20px;border-bottom:1px solid #E2E8F0;">
      <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr>
        <td style="vertical-align:middle;">
          @if($hasFirmLogo)
            <img src="{{ $firmLogo }}" alt="{{ $senderName }}" height="40" style="display:block;height:40px;width:auto;max-width:200px;border:0;outline:none;">
          @else
            <a href="{{ url('/') }}" style="text-decoration:none;color:#1E3A5F;font-family:{{ $font }};font-size:19px;font-weight:700;letter-spacing:-0.01em;">Legal<span style="color:#3A86FF;">Web</span></a>
          @endif
        </td>
        <td align="right" style="vertical-align:middle;font-family:{{ $font }};font-size:12.5px;color:#475569;">
          @if($hasFirmLogo || $audience === 'client')
            {{ $senderName }}
          @else
            Vigilancia judicial
          @endif
        </td>
      </tr></table>
    </td></tr>

    {{-- Contenido --}}
    <tr><td class="lw-pad" style="padding:32px 40px 34px;font-family:{{ $font }};font-size:16px;line-height:1.6;color:#334155;">
      {{ $slot }}
    </td></tr>

    {{-- Pie --}}
    <tr><td class="lw-pad" style="padding:20px 40px 22px;background:#F8FAFC;border-top:1px solid #E2E8F0;border-radius:0 0 12px 12px;font-family:{{ $font }};">
      <p style="margin:0 0 6px;font-size:13px;line-height:1.55;color:#475569;">
        @if($audience === 'client')
          Mensaje de {{ $senderName }} enviado a través de {{ $appName }}.
        @else
          {{ $appName }} &middot; Vigilancia de procesos judiciales para abogados en Colombia.
        @endif
      </p>
      <p style="margin:0;font-size:13px;line-height:1.55;color:#475569;">
        <a href="{{ url('/portal/terminos') }}" style="color:#475569;text-decoration:underline;">Términos</a>
        &nbsp;&middot;&nbsp;
        <a href="{{ url('/portal/privacidad') }}" style="color:#475569;text-decoration:underline;">Privacidad</a>
        @if($audience === 'account')
          &nbsp;&middot;&nbsp;
          <a href="{{ url('/admin/firm-settings') }}" style="color:#475569;text-decoration:underline;">Ajustar avisos</a>
        @endif
        @if(filled($unsubscribeUrl))
          &nbsp;&middot;&nbsp;
          <a href="{{ $unsubscribeUrl }}" style="color:#475569;text-decoration:underline;">Cancelar suscripción</a>
        @endif
      </p>
    </td></tr>

  </table>

  <table class="lw-container" width="600" cellpadding="0" cellspacing="0" role="presentation" style="width:600px;max-width:600px;">
    <tr><td class="lw-pad" style="padding:16px 40px 0;font-family:{{ $font }};font-size:12px;line-height:1.55;color:#64748B;text-align:center;">
      Para que estos avisos no lleguen a spam, agregue {{ $from }} a sus contactos.<br>
      &copy; {{ now()->format('Y') }} {{ $appName }}
    </td></tr>
  </table>

</td></tr>
</table>
</body>
</html>
