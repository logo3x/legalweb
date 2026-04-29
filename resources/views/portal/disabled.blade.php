<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal del Cliente - {{ $firmName ?? 'LegalWeb' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', system-ui, sans-serif; }
        body { background: linear-gradient(135deg, #F5F7FA 0%, #EBF0FF 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; color: #1f2937; }
        .card { background: #fff; border-radius: 16px; padding: 40px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 20px 60px rgba(15, 23, 42, .08); border: 1px solid rgba(15, 23, 42, .04); }
        .logo { max-height: 60px; margin-bottom: 24px; }
        .icon { width: 72px; height: 72px; border-radius: 50%; background: #fef3c7; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; }
        h1 { font-size: 22px; font-weight: 700; color: #1E3A5F; margin-bottom: 12px; }
        p { color: #6b7280; line-height: 1.6; font-size: 15px; margin-bottom: 12px; }
        .firm { margin-top: 24px; padding-top: 24px; border-top: 1px solid #e5e7eb; font-size: 13px; color: #9ca3af; }
        .firm strong { color: #374151; }
    </style>
</head>
<body>
    <div class="card">
        @if($firmLogo)
            <img src="{{ $firmLogo }}" alt="{{ $firmName }}" class="logo">
        @endif

        <div class="icon">
            <svg width="36" height="36" fill="none" stroke="#d97706" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
            </svg>
        </div>

        <h1>Portal temporalmente desactivado</h1>
        <p>El abogado responsable de su caso ha desactivado temporalmente el acceso a este portal.</p>
        <p>Si necesita consultar el estado de su proceso, comun&iacute;quese directamente con su abogado para que lo reactive.</p>

        @if($firmName)
            <div class="firm">
                <strong>{{ $firmName }}</strong>
            </div>
        @endif
    </div>
</body>
</html>
