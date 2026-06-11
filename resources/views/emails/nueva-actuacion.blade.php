@php
    $appName = config('app.name', 'LegalWeb');
    $logoUrl = url('/images/logo-icon-email.png');
    $year = now()->format('Y');
    $eventTypeLabel = strtoupper($eventType ?? 'ACTUACIÓN');
    $eventDateLabel = $eventDate ?? '';
    $termsUrl = url('/portal/terminos');
    $privacyUrl = url('/portal/privacidad');
    $settingsUrl = url('/admin/firm-settings');
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="color-scheme" content="light">
<title>Nueva actuaci&oacute;n &mdash; {{ $appName }}</title>
<style>
  @media only screen and (max-width: 600px) {
    .container { width: 100% !important; }
    .content { padding: 28px 22px !important; }
    .btn { display: block !important; }
    .det td { display: block !important; width: 100% !important; padding: 6px 0 !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background:#F5F7FA;font-family:'Inter','Segoe UI',Helvetica,Arial,sans-serif;color:#374151;-webkit-font-smoothing:antialiased;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#F5F7FA;padding:32px 12px;">
<tr><td align="center">
  <table class="container" width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(15,23,42,0.07);">

    <tr><td align="center" bgcolor="#1E3A5F" style="padding:30px 24px 26px;background-color:#1E3A5F;background-image:linear-gradient(135deg,#1E3A5F 0%,#2C4A75 100%);">
      <img src="{{ $logoUrl }}" alt="{{ $appName }}" width="64" height="64" style="display:block;width:64px;height:64px;border:0;outline:none;margin:0 auto 8px;">
      <div style="color:#ffffff;font-size:22px;font-weight:800;letter-spacing:-0.02em;">{{ $appName }}</div>
      <div style="color:rgba(255,255,255,0.78);font-size:12.5px;margin-top:3px;font-weight:500;letter-spacing:.04em;text-transform:uppercase;">Vigilancia judicial</div>
    </td></tr>
    <tr><td style="height:4px;background:#3A86FF;font-size:0;line-height:0;">&nbsp;</td></tr>

    <tr><td class="content" style="padding:36px 40px;">

      <table cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background:#FEF2F2;border-radius:999px;padding:6px 14px;white-space:nowrap;">
        <span style="display:inline-block;width:7px;height:7px;background:#EF4444;border-radius:50%;vertical-align:middle;margin-right:7px;"></span>
        <span style="color:#DC2626;font-size:12px;font-weight:700;letter-spacing:.03em;vertical-align:middle;">NUEVA ACTUACI&Oacute;N DETECTADA</span>
      </td></tr></table>

      <h1 style="margin:18px 0 8px 0;font-size:23px;font-weight:700;color:#1E3A5F;line-height:1.3;letter-spacing:-0.01em;">Hola, {{ $lawyerName }}</h1>
      <p style="margin:0 0 22px 0;font-size:15px;line-height:1.65;color:#374151;">
        Durante la sincronizaci&oacute;n con la Rama Judicial detectamos
        <strong>{{ $newCount > 1 ? $newCount.' nuevas actuaciones' : 'una nueva actuaci&oacute;n' }}</strong>
        en uno de tus procesos vigilados:
      </p>

      <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;">
        <tr><td style="padding:20px 22px;">
          <table cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background:#EFF6FF;border-radius:6px;padding:4px 10px;white-space:nowrap;">
            <span style="color:#1D4ED8;font-size:11.5px;font-weight:700;letter-spacing:.03em;">{{ $eventTypeLabel }}@if($eventDateLabel) &middot; {{ strtoupper($eventDateLabel) }}@endif</span>
          </td></tr></table>
          <div style="font-size:19px;font-weight:700;color:#1E3A5F;margin:12px 0 16px;line-height:1.3;">{{ $eventTitle ?? 'Nueva actuaci&oacute;n' }}</div>

          <table class="det" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="font-size:13.5px;">
            <tr>
              <td width="50%" style="padding:7px 0;vertical-align:top;"><div style="color:#94A3B8;font-size:12px;margin-bottom:2px;">Radicado</div><div style="color:#1E3A5F;font-weight:600;font-family:'SF Mono',Menlo,Consolas,monospace;font-size:12.5px;">{{ $radicado ?? '-' }}</div></td>
              <td width="50%" style="padding:7px 0;vertical-align:top;"><div style="color:#94A3B8;font-size:12px;margin-bottom:2px;">Despacho</div><div style="color:#1E3A5F;font-weight:600;">{{ $despacho ?? '-' }}</div></td>
            </tr>
            <tr>
              <td width="50%" style="padding:7px 0;vertical-align:top;"><div style="color:#94A3B8;font-size:12px;margin-bottom:2px;">Cliente</div><div style="color:#1E3A5F;font-weight:600;">{{ $clientName ?? '-' }}</div></td>
              <td width="50%" style="padding:7px 0;vertical-align:top;"><div style="color:#94A3B8;font-size:12px;margin-bottom:2px;">Caso</div><div style="color:#1E3A5F;font-weight:600;">{{ $caseTitle ?? '-' }}</div></td>
            </tr>
          </table>
        </td></tr>
      </table>

      <table cellpadding="0" cellspacing="0" role="presentation" style="margin:26px 0 6px;"><tr>
        <td align="center" style="border-radius:10px;background:#3A86FF;box-shadow:0 6px 16px rgba(58,134,255,0.28);">
          <a class="btn" href="{{ $caseUrl }}" target="_blank" style="display:inline-block;padding:14px 34px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:10px;">Ver actuaci&oacute;n en {{ $appName }} &rarr;</a>
        </td>
      </tr></table>

      <p style="margin:22px 0 0 0;font-size:14px;line-height:1.65;color:#64748B;">
        Seguiremos vigilando este proceso por ti. Te avisaremos ante cualquier novedad &mdash;
        no necesitas entrar a consultar la Rama Judicial manualmente.
      </p>

      <div style="margin-top:26px;padding-top:20px;border-top:1px solid #e5e7eb;">
        <p style="margin:0;font-size:14px;color:#6b7280;line-height:1.6;">
          Atentamente,<br><strong style="color:#1E3A5F;">Equipo {{ $appName }}</strong>
        </p>
      </div>
    </td></tr>

    <tr><td style="padding:22px 32px;background:#F9FAFB;border-top:1px solid #e5e7eb;text-align:center;">
      <p style="margin:0 0 6px 0;font-size:12px;color:#6b7280;line-height:1.6;">
        Este mensaje fue enviado autom&aacute;ticamente. Por favor no respondas a este correo.
      </p>
      <p style="margin:0 0 8px 0;font-size:12px;color:#9ca3af;line-height:1.6;">
        <a href="{{ $termsUrl }}" style="color:#6b7280;text-decoration:underline;">T&eacute;rminos y Condiciones</a> &nbsp;&middot;&nbsp;
        <a href="{{ $privacyUrl }}" style="color:#6b7280;text-decoration:underline;">Pol&iacute;tica de Privacidad</a> &nbsp;&middot;&nbsp;
        <a href="{{ $settingsUrl }}" style="color:#6b7280;text-decoration:underline;">Ajustar notificaciones</a>
      </p>
      <p style="margin:0;font-size:11px;color:#9ca3af;">&copy; {{ $year }} {{ $appName }}. Conectado con la Rama Judicial de Colombia.</p>
    </td></tr>

  </table>
</td></tr>
</table>
</body>
</html>
