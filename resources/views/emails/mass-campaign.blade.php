@php
    $appName = config('app.name', 'LegalWeb');
    $logoUrl = url('/images/logo-icon-email.png');
    $year = now()->format('Y');
    $termsUrl = url('/portal/terminos');
    $privacyUrl = url('/portal/privacidad');
    $siteUrl = url('/');
    $loginUrl = url('/admin/login');

    // $body es el HTML ya con los reemplazos hechos ({{name}}, {{firm}}, etc.)
    // $subject viene del MailMessage
    // $previewText opcional: aparece como preview en bandeja
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="color-scheme" content="light">
<title>{{ $subject ?? $appName }}</title>
<style>
  /* Estilos base sobre los inline (gmail soporta lo que sobreviva) */
  .lw-mc a { color: #1D4ED8; }
  .lw-mc h2, .lw-mc h3 { font-family: 'Poppins','Helvetica Neue',Arial,sans-serif !important; }

  /* Bloques reutilizables que pueden venir en el body */
  .lw-callout { background: #EFF6FF; border-left: 4px solid #3A86FF; padding: 16px 20px; border-radius: 0 10px 10px 0; margin: 20px 0; color: #1E3A8A; font-size: 15px; line-height: 1.55; }
  .lw-callout.success { background: #D1FAE5; border-left-color: #059669; color: #064E3B; }
  .lw-callout.warning { background: #FEF3C7; border-left-color: #D97706; color: #78350F; }
  .lw-callout.danger { background: #FEE2E2; border-left-color: #DC2626; color: #7F1D1D; }
  .lw-callout.brand { background: #1E3A5F; border-left-color: #3A86FF; color: #fff; }
  .lw-callout.brand a { color: #BFDBFE !important; }

  .lw-check-list { list-style: none; padding: 0; margin: 20px 0; }
  .lw-check-list li { padding: 6px 0 6px 28px; position: relative; font-size: 15.5px; color: #334155; line-height: 1.55; }
  .lw-check-list li::before { content: '\2713'; position: absolute; left: 0; top: 6px; width: 20px; height: 20px; background: #D1FAE5; color: #059669; border-radius: 50%; text-align: center; font-size: 12px; font-weight: 800; line-height: 20px; }

  .lw-num-list { counter-reset: lw-num; list-style: none; padding: 0; margin: 20px 0; }
  .lw-num-list li { counter-increment: lw-num; padding: 10px 0 10px 44px; position: relative; font-size: 15.5px; color: #334155; line-height: 1.55; }
  .lw-num-list li::before { content: counter(lw-num); position: absolute; left: 0; top: 8px; width: 30px; height: 30px; background: #3A86FF; color: #fff; border-radius: 50%; text-align: center; font-family: 'Poppins',Arial,sans-serif; font-size: 14px; font-weight: 700; line-height: 30px; }

  .lw-stat-row { display: table; width: 100%; margin: 22px 0; border-collapse: separate; border-spacing: 10px 0; }
  .lw-stat { display: table-cell; background: #F1F5F9; border-radius: 10px; padding: 18px 14px; text-align: center; vertical-align: top; width: 33%; }
  .lw-stat-num { display: block; font-family: 'Poppins',Arial,sans-serif; font-size: 28px; font-weight: 800; color: #1E3A5F; letter-spacing: -.02em; line-height: 1; margin-bottom: 4px; }
  .lw-stat-lbl { display: block; font-size: 11.5px; color: #64748B; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; }

  .lw-divider { border: 0; border-top: 1px solid #E2E8F0; margin: 28px 0; }

  .lw-pill { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 11.5px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; background: #DBEAFE; color: #1E3A8A; }
  .lw-pill.success { background: #D1FAE5; color: #065F46; }
  .lw-pill.warning { background: #FEF3C7; color: #78350F; }
  .lw-pill.brand { background: #1E3A5F; color: #fff; }

  .lw-cta-block { background: linear-gradient(135deg, #1E3A5F 0%, #2C4A75 100%); border-radius: 14px; padding: 28px 26px; margin: 26px 0; color: #fff; }
  .lw-cta-block h3 { color: #fff !important; margin: 0 0 8px; font-family: 'Poppins',Arial,sans-serif; font-size: 20px; font-weight: 700; }
  .lw-cta-block p { color: rgba(255,255,255,.78); margin: 0 0 16px; font-size: 14.5px; line-height: 1.55; }
  .lw-cta-block .lw-btn { display: inline-block; padding: 12px 26px; background: #3A86FF; color: #fff !important; text-decoration: none; border-radius: 10px; font-size: 14.5px; font-weight: 600; }

  .lw-btn-primary { display: inline-block; padding: 13px 28px; background: #3A86FF; color: #fff !important; text-decoration: none; border-radius: 10px; font-size: 15px; font-weight: 600; }
  .lw-btn-outline { display: inline-block; padding: 12px 26px; background: #fff; color: #1E3A5F !important; text-decoration: none; border-radius: 10px; font-size: 14.5px; font-weight: 600; border: 1.5px solid #CBD5E1; }

  .lw-card { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 20px 22px; margin: 18px 0; }
  .lw-card-row { display: table; width: 100%; }
  .lw-card-ico { display: table-cell; width: 48px; vertical-align: top; padding-right: 14px; }
  .lw-card-ico div { width: 40px; height: 40px; border-radius: 10px; background: #EFF6FF; color: #3A86FF; text-align: center; line-height: 40px; font-size: 20px; font-weight: 800; }
  .lw-card-body { display: table-cell; vertical-align: top; }
  .lw-card-body h4 { margin: 0 0 4px; font-family: 'Poppins',Arial,sans-serif; font-size: 15.5px; font-weight: 700; color: #1E3A5F; }
  .lw-card-body p { margin: 0; font-size: 14px; color: #64748B; line-height: 1.55; }

  .lw-mc h2 { font-size: 24px; font-weight: 800; color: #0F172A; margin: 28px 0 14px; letter-spacing: -.02em; line-height: 1.25; }
  .lw-mc h3 { font-size: 18px; font-weight: 700; color: #1E3A5F; margin: 22px 0 10px; letter-spacing: -.01em; }
  .lw-mc p { font-size: 16px; line-height: 1.65; color: #334155; margin: 0 0 14px; }
  .lw-mc p.small { font-size: 14px; color: #64748B; }
  .lw-mc strong { color: #0F172A; font-weight: 700; }

  @media only screen and (max-width: 600px) {
    .lw-container { width: 100% !important; }
    .lw-content { padding: 30px 20px !important; }
    .lw-mc h2 { font-size: 22px !important; }
    .lw-stat-row { border-spacing: 6px 0; }
    .lw-stat { padding: 12px 6px; }
    .lw-stat-num { font-size: 22px !important; }
    .lw-btn-primary, .lw-btn-outline, .lw-cta-block .lw-btn { display: block !important; text-align: center; }
  }
</style>
</head>
<body style="margin:0;padding:0;background:#F1F5F9;font-family:'Inter','Helvetica Neue',Arial,sans-serif;color:#1E293B;-webkit-font-smoothing:antialiased;">

@if(!empty($previewText))
<div style="display:none;font-size:1px;color:#F1F5F9;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ $previewText }}</div>
@endif

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#F1F5F9;padding:40px 12px;">
<tr><td align="center">

  <table class="lw-container" width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 4px 24px rgba(15,23,42,.08);">

    {{-- Header navy con logo --}}
    <tr><td align="center" style="padding:30px 24px 24px;background-color:#1E3A5F;">
      <a href="{{ $siteUrl }}" style="text-decoration:none;">
        <img src="{{ $logoUrl }}" alt="{{ $appName }}" width="52" height="52" style="display:block;width:52px;height:52px;border:0;outline:none;margin:0 auto 10px;">
        <div style="color:#ffffff;font-size:18px;font-weight:800;letter-spacing:-0.01em;font-family:'Poppins','Inter',sans-serif;">{{ $appName }}</div>
      </a>
    </td></tr>
    <tr><td style="height:4px;background:#3A86FF;font-size:0;line-height:0;">&nbsp;</td></tr>

    {{-- Body editorial: el body HTML viene tal cual del seeder --}}
    <tr><td class="lw-content lw-mc" style="padding:42px 48px 32px;">
      {!! $body !!}
    </td></tr>

    {{-- Footer con redes y unsubscribe --}}
    <tr><td style="background:#F8FAFC;padding:26px 48px;border-top:1px solid #E2E8F0;">
      <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
          <td style="vertical-align:top;">
            <p style="margin:0 0 6px;font-size:13.5px;color:#475569;line-height:1.55;font-weight:500;">
              <strong style="color:#1E3A5F;font-family:'Poppins','Inter',sans-serif;">{{ $appName }}</strong>
              &middot; Vigilancia judicial automatica.
            </p>
            <p style="margin:0;font-size:12.5px;color:#94A3B8;line-height:1.55;">
              <a href="{{ $siteUrl }}" style="color:#64748B;text-decoration:underline;">legalweb.com.co</a>
              &middot;
              <a href="{{ $loginUrl }}" style="color:#64748B;text-decoration:underline;">Entrar</a>
              &middot;
              <a href="{{ $termsUrl }}" style="color:#64748B;text-decoration:underline;">Terminos</a>
              &middot;
              <a href="{{ $privacyUrl }}" style="color:#64748B;text-decoration:underline;">Privacidad</a>
            </p>
          </td>
        </tr>
      </table>
    </td></tr>

  </table>

  {{-- Sub-footer fuera del card --}}
  <table class="lw-container" width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;margin-top:18px;">
    <tr><td align="center" style="padding:0 24px;">
      <p style="margin:0;font-size:11.5px;color:#94A3B8;line-height:1.55;">
        Recibes este correo porque eres usuario de {{ $appName }}.<br>
        &copy; {{ $year }} {{ $appName }} &middot; Hecho en Colombia.
      </p>
    </td></tr>
  </table>

</td></tr>
</table>
</body>
</html>
