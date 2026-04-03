<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal del Cliente') - LegalWeb</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                @if(isset($portalToken))
                    <a href="{{ route('portal.show', $portalToken) }}" class="flex items-center gap-2 hover:opacity-80">
                @endif
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                    </svg>
                    <span class="text-xl font-bold text-gray-800">LegalWeb</span>
                    <span class="text-sm text-gray-500 ml-2">Portal del Cliente</span>
                @if(isset($portalToken))
                    </a>
                @endif
            </div>
            <div class="flex items-center gap-4 text-sm text-gray-500">
                @if(isset($portalToken))
                    <a href="{{ route('portal.show', $portalToken) }}" class="flex items-center gap-1 hover:text-blue-600 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Mi Caso
                    </a>
                @endif
                <a href="{{ route('portal.terms', isset($portalToken) ? ['ref' => $portalToken] : []) }}" class="hover:text-blue-600">Terminos</a>
                <a href="{{ route('portal.privacy', isset($portalToken) ? ['ref' => $portalToken] : []) }}" class="hover:text-blue-600">Privacidad</a>
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 py-8">
        @if(isset($portalToken) && !request()->routeIs('portal.show'))
            <a href="{{ route('portal.show', $portalToken) }}" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800 mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver a mi caso
            </a>
        @endif
        @yield('content')
    </main>

    <footer class="border-t border-gray-200 mt-12">
        <div class="max-w-5xl mx-auto px-4 py-6 text-center text-sm text-gray-400">
            <p>&copy; {{ date('Y') }} LegalWeb. Esta plataforma es una herramienta tecnologica de gestion.</p>
            <p class="mt-1">No sustituye el criterio profesional del abogado ni garantiza resultados de los procesos legales.</p>
            <div class="mt-2 flex justify-center gap-4">
                <a href="{{ route('portal.terms', isset($portalToken) ? ['ref' => $portalToken] : []) }}" class="hover:text-blue-600">Terminos y Condiciones</a>
                <a href="{{ route('portal.privacy', isset($portalToken) ? ['ref' => $portalToken] : []) }}" class="hover:text-blue-600">Politica de Privacidad</a>
            </div>
        </div>
    </footer>
</body>
</html>
