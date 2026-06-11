@php
    $appName = config('app.name', 'LegalWeb');
    $logoUrl = url('/images/logo-icon-email.png');
    $year = now()->format('Y');
    $termsUrl = url('/portal/terminos');
    $privacyUrl = url('/portal/privacidad');

    // Variables esperadas (pasadas desde MailMessage::view):
    //   $heading     - titulo grande (string)
    //   $eyebrow     - linea pequena arriba del titulo (opcional)
    //   $intro       - parrafo intro (string)
    //   $lines       - lista de parrafos adicionales (array de strings) (opcional)
    //   $highlight   - bloque destacado tipo recuadro (string|null) (opcional)
    //   $actionUrl   - boton CTA href (opcional)
    //   $actionLabel - boton CTA texto (opcional)
    //   $tone        - 'info' | 'warning' | 'danger' (default 'info')
    $tone = $tone ?? 'info';
    $toneColor = match($tone) {
        'danger' => '#DC2626',
        'warning' => '#D97706',
        default => '#3A86FF',
    };
    $toneBg = match($tone) {
        'danger' => '#FEE2E2',
        'warning' => '#FEF3C7',
        default => '#EFF6FF',
    };
    $lines = $lines ?? [];
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="color-scheme" content="light">
<title>{{ $heading ?? $appName }}</title>
<style>
  @media only screen and (max-width: 600px) {
    .container { width: 100% !important; }
    .content { padding: 28px 22px !important; }
    .btn { display: block !important; width: 100% !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background:#F5F7FA;font-family:'Inter','Segoe UI',Helvetica,Arial,sans-serif;color:#1E293B;-webkit-font-smoothing:antialiased;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#F5F7FA;padding:32px 12px;">
<tr><td align="center">
  <table class="container" width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(15,23,42,0.07);">

    {{-- Header --}}
    <tr><td align="center" bgcolor="#1E3A5F" style="padding:30px 24px 26px;background-color:#1E3A5F;">
      <img src="{{ $logoUrl }}" alt="{{ $appName }}" width="56" height="56" style="display:block;width:56px;height:56px;border:0;outline:none;margin:0 auto 10px;">
      <div style="color:#ffffff;font-size:20px;font-weight:800;letter-spacing:-0.02em;">{{ $appName }}</div>
    </td></tr>
    <tr><td style="height:4px;background:{{ $toneColor }};font-size:0;line-height:0;">&nbsp;</td></tr>

    {{-- Content --}}
    <tr><td class="content" style="padding:36px 40px;">

      @if(!empty($eyebrow))
        <div style="font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:{{ $toneColor }};margin-bottom:12px;">{{ $eyebrow }}</div>
      @endif

      <h1 style="margin:0 0 16px;font-size:24px;font-weight:800;color:#1E3A5F;letter-spacing:-0.02em;line-height:1.25;">{{ $heading ?? '' }}</h1>

      @if(!empty($intro))
        <p style="margin:0 0 14px;font-size:15.5px;line-height:1.65;color:#334155;">{!! $intro !!}</p>
      @endif

      @foreach($lines as $line)
        <p style="margin:0 0 14px;font-size:15.5px;line-height:1.65;color:#334155;">{!! $line !!}</p>
      @endforeach

      @if(!empty($highlight))
        <div style="margin:20px 0;padding:18px 20px;background:{{ $toneBg }};border-radius:10px;border-left:0;font-size:15px;color:#1E293B;line-height:1.55;">
          {!! $highlight !!}
        </div>
      @endif

      @if(!empty($actionUrl) && !empty($actionLabel))
        <table cellpadding="0" cellspacing="0" role="presentation" style="margin:24px 0 8px;">
          <tr><td bgcolor="{{ $toneColor }}" style="border-radius:10px;">
            <a class="btn" href="{{ $actionUrl }}" target="_blank" style="display:inline-block;padding:14px 28px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:10px;background-color:{{ $toneColor }};">{{ $actionLabel }}</a>
          </td></tr>
        </table>
      @endif

      @if(!empty($footerNote))
        <p style="margin:18px 0 0;font-size:13.5px;line-height:1.6;color:#64748B;">{!! $footerNote !!}</p>
      @endif

    </td></tr>

    {{-- Footer --}}
    <tr><td style="background:#F8FAFC;padding:22px 40px;border-top:1px solid #E2E8F0;">
      <p style="margin:0 0 6px;font-size:12.5px;color:#64748B;line-height:1.55;">
        <strong style="color:#1E3A5F;">{{ $appName }}</strong> &middot; Vigilancia judicial automatica para abogados.
      </p>
      <p style="margin:0;font-size:12px;color:#94A3B8;">
        <a href="{{ $termsUrl }}" style="color:#64748B;text-decoration:underline;">Terminos</a> &middot;
        <a href="{{ $privacyUrl }}" style="color:#64748B;text-decoration:underline;">Privacidad</a> &middot;
        &copy; {{ $year }} {{ $appName }}
      </p>
    </td></tr>

  </table>
</td></tr>
</table>
</body>
</html>
