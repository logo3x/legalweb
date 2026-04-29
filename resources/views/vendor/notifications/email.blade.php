@php
    $appName = config('app.name', 'LegalWeb');
    $logoUrl = url('/images/logo-square.png');
    $year = now()->format('Y');
    $primaryColor = '#3A86FF';
    $brandColor = '#1E3A5F';
    $btnColor = match ($level ?? 'info') {
        'success' => '#10B981',
        'error' => '#EF4444',
        default => '#3A86FF',
    };
    $accentColor = match ($level ?? 'info') {
        'success' => '#10B981',
        'error' => '#EF4444',
        default => '#3A86FF',
    };
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $appName }}</title>
    <style>
        @media only screen and (max-width: 600px) {
            .container { width: 100% !important; }
            .content { padding: 24px 18px !important; }
            .btn { display: block !important; width: auto !important; }
            .header-band { padding: 22px 18px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#F5F7FA;font-family:'Inter','Segoe UI',Helvetica,Arial,sans-serif;color:#374151;-webkit-font-smoothing:antialiased;">

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#F5F7FA;padding:32px 12px;">
        <tr>
            <td align="center">

                <table class="container" width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(15,23,42,0.06);">

                    {{-- Header con logo --}}
                    <tr>
                        <td class="header-band" align="center" style="padding:28px 24px;background:linear-gradient(135deg,{{ $brandColor }} 0%,#2C4A75 100%);">
                            <a href="{{ url('/') }}" style="text-decoration:none;display:inline-block;">
                                <img src="{{ $logoUrl }}" alt="{{ $appName }}" width="48" height="48" style="display:block;border-radius:10px;margin:0 auto 8px;">
                                <div style="color:#ffffff;font-size:20px;font-weight:700;letter-spacing:-0.01em;">{{ $appName }}</div>
                                <div style="color:rgba(255,255,255,0.75);font-size:12px;margin-top:2px;">Control inteligente de procesos legales</div>
                            </a>
                        </td>
                    </tr>

                    {{-- Indicador de nivel (color band) --}}
                    @if(($level ?? 'info') !== 'info')
                        <tr>
                            <td style="height:4px;background:{{ $accentColor }};font-size:0;line-height:0;">&nbsp;</td>
                        </tr>
                    @endif

                    {{-- Cuerpo del mensaje --}}
                    <tr>
                        <td class="content" style="padding:36px 40px;">

                            {{-- Saludo --}}
                            @if(! empty($greeting))
                                <h1 style="margin:0 0 18px 0;font-size:22px;font-weight:700;color:{{ $brandColor }};line-height:1.3;letter-spacing:-0.01em;">
                                    {{ $greeting }}
                                </h1>
                            @else
                                <h1 style="margin:0 0 18px 0;font-size:22px;font-weight:700;color:{{ $brandColor }};line-height:1.3;">
                                    @if(($level ?? '') === 'error')
                                        Atenci&oacute;n
                                    @else
                                        Hola
                                    @endif
                                </h1>
                            @endif

                            {{-- Lineas introductorias --}}
                            @foreach($introLines as $line)
                                <p style="margin:0 0 14px 0;font-size:15px;line-height:1.65;color:#374151;">
                                    {!! Illuminate\Mail\Markdown::parse($line) !!}
                                </p>
                            @endforeach

                            {{-- Boton de accion --}}
                            @isset($actionText)
                                <table cellpadding="0" cellspacing="0" role="presentation" style="margin:24px 0;">
                                    <tr>
                                        <td align="center" style="border-radius:10px;background:{{ $btnColor }};box-shadow:0 6px 16px rgba(58,134,255,0.28);">
                                            <a class="btn" href="{{ $actionUrl }}" target="_blank" style="display:inline-block;padding:14px 32px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:10px;">
                                                {{ $actionText }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endisset

                            {{-- Lineas de cierre --}}
                            @foreach($outroLines as $line)
                                <p style="margin:0 0 14px 0;font-size:15px;line-height:1.65;color:#374151;">
                                    {!! Illuminate\Mail\Markdown::parse($line) !!}
                                </p>
                            @endforeach

                            {{-- Despedida --}}
                            <div style="margin-top:28px;padding-top:20px;border-top:1px solid #e5e7eb;">
                                @if(! empty($salutation))
                                    <p style="margin:0;font-size:14px;color:#6b7280;line-height:1.6;">
                                        {!! Illuminate\Mail\Markdown::parse($salutation) !!}
                                    </p>
                                @else
                                    <p style="margin:0;font-size:14px;color:#6b7280;line-height:1.6;">
                                        Atentamente,<br>
                                        <strong style="color:{{ $brandColor }};">{{ $appName }}</strong>
                                    </p>
                                @endif
                            </div>

                            {{-- Subcopy / fallback URL --}}
                            @isset($actionText)
                                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:24px;background:#F9FAFB;border-radius:8px;">
                                    <tr>
                                        <td style="padding:14px 16px;font-size:12px;color:#6b7280;line-height:1.6;">
                                            <span style="color:#9ca3af;">&iquest;Tiene problemas con el bot&oacute;n?</span> Copie y pegue este enlace en su navegador:<br>
                                            <a href="{{ $actionUrl }}" style="color:{{ $primaryColor }};word-break:break-all;text-decoration:none;">{{ $displayableActionUrl ?? $actionUrl }}</a>
                                        </td>
                                    </tr>
                                </table>
                            @endisset

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:24px 32px;background:#F9FAFB;border-top:1px solid #e5e7eb;text-align:center;">
                            <p style="margin:0 0 6px 0;font-size:12px;color:#6b7280;line-height:1.6;">
                                Este mensaje fue enviado autom&aacute;ticamente. Por favor no responda a este correo.
                            </p>
                            <p style="margin:0 0 10px 0;font-size:12px;color:#9ca3af;line-height:1.6;">
                                <a href="{{ url('/portal/terminos') }}" style="color:#6b7280;text-decoration:underline;">T&eacute;rminos y Condiciones</a>
                                &nbsp;&middot;&nbsp;
                                <a href="{{ url('/portal/privacidad') }}" style="color:#6b7280;text-decoration:underline;">Pol&iacute;tica de Privacidad</a>
                            </p>
                            <p style="margin:0;font-size:11px;color:#9ca3af;">
                                &copy; {{ $year }} {{ $appName }}. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
