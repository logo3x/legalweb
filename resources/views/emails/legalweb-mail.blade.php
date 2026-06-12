@php
    $appName = config('app.name', 'LegalWeb');
    $logoUrl = url('/images/logo-icon-email.png');
    $year = now()->format('Y');
    $termsUrl = url('/portal/terminos');
    $privacyUrl = url('/portal/privacidad');

    // Variables esperadas (pasadas desde MailMessage::view):
    //   $heading     - titulo grande (string)
    //   $eyebrow     - linea pequena arriba del titulo (opcional)
    //   $intro       - parrafo intro (string, acepta HTML inline)
    //   $lines       - lista de parrafos adicionales (array de strings) (opcional)
    //   $highlight   - bloque destacado tipo recuadro (string|null) (opcional)
    //   $actionUrl   - boton CTA href (opcional)
    //   $actionLabel - boton CTA texto (opcional)
    //   $tone        - 'info' | 'warning' | 'danger' | 'success' (default 'info')
    //   $footerNote  - parrafo final (opcional)
    $tone = $tone ?? 'info';
    [$toneColor, $toneBg, $toneInk] = match ($tone) {
        'danger' => ['#DC2626', '#FEE2E2', '#7F1D1D'],
        'warning' => ['#D97706', '#FEF3C7', '#78350F'],
        'success' => ['#059669', '#D1FAE5', '#064E3B'],
        default => ['#3A86FF', '#EFF6FF', '#1E3A8A'],
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
<meta name="supported-color-schemes" content="light">
<title>{{ $heading ?? $appName }}</title>
<!--[if mso]>
<style type="text/css">
  body, table, td, a { font-family: Arial, sans-serif !important; }
</style>
<![endif]-->
<style>
  @media only screen and (max-width: 600px) {
    .lw-container { width: 100% !important; }
    .lw-content { padding: 32px 22px !important; }
    .lw-btn { display: block !important; width: 100% !important; box-sizing: border-box; }
    .lw-h1 { font-size: 22px !important; line-height: 1.22 !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background:#F1F5F9;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#1E293B;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#F1F5F9;padding:40px 12px;">
<tr><td align="center">

  <table class="lw-container" width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 4px 24px rgba(15,23,42,0.08), 0 1px 3px rgba(15,23,42,0.05);">

    {{-- Header: navy con logo grande y banda de color por tone --}}
    <tr><td align="center" style="padding:36px 24px 28px;background-color:#1E3A5F;">
      <img src="{{ $logoUrl }}" alt="{{ $appName }}" width="56" height="56" style="display:block;width:56px;height:56px;border:0;outline:none;margin:0 auto 12px;">
      <div style="color:#ffffff;font-size:18px;font-weight:800;letter-spacing:-0.01em;font-family:'Poppins','Inter',sans-serif;">{{ $appName }}</div>
      <div style="color:rgba(255,255,255,0.62);font-size:11px;margin-top:4px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;">Vigilancia judicial</div>
    </td></tr>
    <tr><td style="height:4px;background:{{ $toneColor }};font-size:0;line-height:0;">&nbsp;</td></tr>

    {{-- Content --}}
    <tr><td class="lw-content" style="padding:44px 48px 36px;">

      @if(!empty($eyebrow))
        <div style="display:inline-block;padding:5px 11px;background:{{ $toneBg }};color:{{ $toneInk }};font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;border-radius:999px;margin-bottom:18px;">{{ $eyebrow }}</div>
      @endif

      <h1 class="lw-h1" style="margin:0 0 18px;font-family:'Poppins','Inter',sans-serif;font-size:26px;font-weight:800;color:#0F172A;letter-spacing:-0.02em;line-height:1.22;">{{ $heading ?? '' }}</h1>

      @if(!empty($intro))
        <p style="margin:0 0 16px;font-size:16px;line-height:1.65;color:#334155;">{!! $intro !!}</p>
      @endif

      @foreach($lines as $line)
        <p style="margin:0 0 16px;font-size:16px;line-height:1.65;color:#334155;">{!! $line !!}</p>
      @endforeach

      @if(!empty($highlight))
        <table cellpadding="0" cellspacing="0" role="presentation" style="margin:22px 0;width:100%;">
          <tr><td style="padding:20px 22px;background:{{ $toneBg }};border-radius:12px;font-size:15.5px;color:{{ $toneInk }};line-height:1.55;font-weight:500;">
            {!! $highlight !!}
          </td></tr>
        </table>
      @endif

      @if(!empty($actionUrl) && !empty($actionLabel))
        <table cellpadding="0" cellspacing="0" role="presentation" style="margin:28px 0 12px;">
          <tr><td bgcolor="{{ $toneColor }}" style="border-radius:10px;">
            <a class="lw-btn" href="{{ $actionUrl }}" target="_blank" style="display:inline-block;padding:14px 30px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:10px;background-color:{{ $toneColor }};font-family:'Inter',sans-serif;letter-spacing:.01em;">{{ $actionLabel }} &rarr;</a>
          </td></tr>
        </table>
      @endif

      @if(!empty($footerNote))
        <p style="margin:24px 0 0;padding-top:20px;border-top:1px solid #E2E8F0;font-size:14px;line-height:1.6;color:#64748B;">{!! $footerNote !!}</p>
      @endif

    </td></tr>

    {{-- Footer --}}
    <tr><td style="background:#F8FAFC;padding:24px 48px 26px;border-top:1px solid #E2E8F0;">
      <p style="margin:0 0 8px;font-size:13px;color:#475569;line-height:1.55;font-weight:500;">
        <strong style="color:#1E3A5F;font-family:'Poppins','Inter',sans-serif;">{{ $appName }}</strong>
        &middot; Vigilancia judicial automatica para abogados en Colombia.
      </p>
      <p style="margin:0;font-size:12px;color:#94A3B8;line-height:1.55;">
        <a href="{{ $termsUrl }}" style="color:#64748B;text-decoration:underline;">Terminos</a>
        &middot;
        <a href="{{ $privacyUrl }}" style="color:#64748B;text-decoration:underline;">Privacidad</a>
        &middot;
        &copy; {{ $year }} {{ $appName }}
      </p>
    </td></tr>

  </table>

  {{-- Sub-footer fuera del card --}}
  <table class="lw-container" width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;margin-top:18px;">
    <tr><td align="center" style="padding:0 24px;">
      <p style="margin:0;font-size:11.5px;color:#94A3B8;line-height:1.55;">
        Recibe este correo porque tiene una cuenta activa en {{ $appName }}.
      </p>
    </td></tr>
  </table>

</td></tr>
</table>
</body>
</html>
