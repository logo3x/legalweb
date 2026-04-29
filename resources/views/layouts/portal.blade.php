<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal del Cliente') - LegalWeb</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-2Q7KJTB5MT"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','G-2Q7KJTB5MT');</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .portal-gradient { background: linear-gradient(135deg, #1E3A5F 0%, #2d5a8e 100%); }
    </style>
</head>
<body style="background: #f8fafc; min-height: 100vh; margin: 0;">
    {{-- Header --}}
    <header class="portal-gradient" style="padding: 0;">
        <div style="max-width: 900px; margin: 0 auto; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 12px;">
                @if(isset($portalToken))
                    <a href="{{ route('portal.show', $portalToken) }}" style="display: flex; align-items: center; gap: 12px; text-decoration: none;">
                @endif
                    @if(isset($firmLogo) && $firmLogo)
                        <img src="{{ $firmLogo }}" alt="Logo" style="height: 36px; width: 36px; border-radius: 8px; object-fit: cover; border: 2px solid rgba(255,255,255,0.3);">
                    @endif
                    <div>
                        <div style="font-size: 15px; font-weight: 700; color: #fff;">
                            @if(isset($firmName) && $firmName)
                                {{ $firmName }}
                            @else
                                LegalWeb
                            @endif
                        </div>
                        <div style="font-size: 11px; color: rgba(255,255,255,0.6);">Portal del Cliente</div>
                    </div>
                @if(isset($portalToken))
                    </a>
                @endif
            </div>
            <div style="display: flex; align-items: center; gap: 16px; font-size: 12px;">
                @if(isset($portalToken))
                    <a href="{{ route('portal.show', $portalToken) }}" style="color: rgba(255,255,255,0.8); text-decoration: none; display: flex; align-items: center; gap: 4px;">
                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Mi Caso
                    </a>
                @endif
                <a href="{{ route('portal.terms', isset($portalToken) ? ['ref' => $portalToken] : []) }}" style="color: rgba(255,255,255,0.6); text-decoration: none;">Terminos</a>
                <a href="{{ route('portal.privacy', isset($portalToken) ? ['ref' => $portalToken] : []) }}" style="color: rgba(255,255,255,0.6); text-decoration: none;">Privacidad</a>
            </div>
        </div>
    </header>

    <main style="max-width: 900px; margin: 0 auto; padding: 24px 20px;">
        @if(isset($portalToken) && !request()->routeIs('portal.show'))
            <a href="{{ route('portal.show', $portalToken) }}" style="display: inline-flex; align-items: center; gap: 4px; font-size: 13px; color: #3A86FF; text-decoration: none; margin-bottom: 16px;">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver a mi caso
            </a>
        @endif
        @yield('content')
    </main>

    <footer style="border-top: 1px solid #e5e7eb; margin-top: 40px;">
        <div style="max-width: 900px; margin: 0 auto; padding: 20px; text-align: center;">
            <img src="/images/logo.svg?v=4" alt="LegalWeb" style="height: 24px; margin: 0 auto 8px; opacity: 0.5;">
            <p style="font-size: 11px; color: #9ca3af; margin: 0;">Esta plataforma es una herramienta tecnologica de gestion. No sustituye el criterio profesional del abogado.</p>
            <div style="margin-top: 8px; display: flex; justify-content: center; gap: 16px; font-size: 11px;">
                <a href="{{ route('portal.terms', isset($portalToken) ? ['ref' => $portalToken] : []) }}" style="color: #9ca3af; text-decoration: none;">Terminos</a>
                <a href="{{ route('portal.privacy', isset($portalToken) ? ['ref' => $portalToken] : []) }}" style="color: #9ca3af; text-decoration: none;">Privacidad</a>
            </div>
        </div>
    </footer>
</body>
</html>
