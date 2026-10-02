<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Suscripcion cancelada - LegalWeb</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', system-ui, sans-serif; }
        body { background: linear-gradient(135deg, #F5F7FA 0%, #EBF0FF 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; color: #1f2937; }
        .card { background: #fff; border-radius: 16px; padding: 40px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 20px 60px rgba(15, 23, 42, .08); border: 1px solid rgba(15, 23, 42, .04); }
        .icon { width: 72px; height: 72px; border-radius: 50%; background: #dcfce7; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; }
        h1 { font-size: 22px; font-weight: 700; color: #1E3A5F; margin-bottom: 12px; }
        p { color: #6b7280; line-height: 1.6; font-size: 15px; margin-bottom: 12px; }
        .note { margin-top: 24px; padding-top: 24px; border-top: 1px solid #e5e7eb; font-size: 13px; color: #9ca3af; }
        a { color: #3A86FF; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg width="36" height="36" fill="none" stroke="#16a34a" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h1>Listo, no recibiras mas campanas</h1>
        <p>Dejaremos de enviar correos informativos y promocionales a <strong>{{ $email }}</strong>.</p>

        <p class="note">
            Seguiras recibiendo las notificaciones de tu cuenta (vencimientos, actuaciones, pagos), porque son necesarias para el servicio.
            Si cambias de opinion, escribenos a <a href="mailto:legalwebco@gmail.com">legalwebco@gmail.com</a>.
        </p>
    </div>
</body>
</html>
