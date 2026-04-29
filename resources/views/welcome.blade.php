<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LegalWeb - Control inteligente de tus procesos legales</title>
    <meta name="description" content="Plataforma SaaS para abogados en Colombia. Gestiona casos, importa procesos de la Rama Judicial, recibe alertas inteligentes y dale visibilidad a tus clientes.">
    <meta property="og:title" content="LegalWeb - Control inteligente de tus procesos legales">
    <meta property="og:description" content="Sincronizacion automatica con la Rama Judicial. Alertas inteligentes. Asistente IA. Portal del cliente.">
    <meta property="og:type" content="website">
    <link rel="icon" href="/images/favicon.ico" type="image/x-icon">
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-2Q7KJTB5MT"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','G-2Q7KJTB5MT');</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '#1E3A5F', light: '#3A86FF', bg: '#F5F7FA' },
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        html { scroll-behavior: smooth; }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } *, *::before, *::after { animation-duration: 0.001ms !important; transition-duration: 0.001ms !important; } }
        section[id] { scroll-margin-top: 90px; }

        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        @keyframes float-slow { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(20px, -30px); } }
        @keyframes float-slower { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(-30px, 20px); } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes shine { from { background-position: -200% center; } to { background-position: 200% center; } }
        @keyframes pulse-ring { 0% { transform: scale(0.8); opacity: 1; } 100% { transform: scale(2); opacity: 0; } }
        .float { animation: float 6s ease-in-out infinite; }
        .blob { position: absolute; border-radius: 50%; filter: blur(60px); opacity: 0.4; pointer-events: none; }
        .blob-1 { animation: float-slow 12s ease-in-out infinite; }
        .blob-2 { animation: float-slower 16s ease-in-out infinite; }
        .fade-in { animation: fadeInUp 0.8s ease-out forwards; }
        .fade-in-delay-1 { animation-delay: 0.2s; opacity: 0; }
        .fade-in-delay-2 { animation-delay: 0.4s; opacity: 0; }
        .fade-in-delay-3 { animation-delay: 0.6s; opacity: 0; }
        .gradient-text { background: linear-gradient(135deg, #3A86FF, #1E3A5F); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-gradient { background: linear-gradient(180deg, #F5F7FA 0%, #EBF0FF 50%, #F5F7FA 100%); }
        .card-hover { transition: transform .35s cubic-bezier(.2,.8,.2,1), box-shadow .35s, border-color .35s; }
        .card-hover:hover { transform: translateY(-6px); box-shadow: 0 18px 50px rgba(58, 134, 255, 0.18); }
        .feature-icon { transition: all 0.3s; }
        .card-hover:hover .feature-icon { transform: scale(1.12) rotate(-3deg); }

        /* Scroll reveal */
        .reveal { opacity: 0; transform: translateY(28px); transition: opacity .7s cubic-bezier(.2,.8,.2,1), transform .7s cubic-bezier(.2,.8,.2,1); will-change: transform, opacity; }
        .reveal.is-visible { opacity: 1; transform: translateY(0); }
        .reveal-zoom { opacity: 0; transform: scale(.96); transition: opacity .7s ease-out, transform .7s ease-out; }
        .reveal-zoom.is-visible { opacity: 1; transform: scale(1); }
        .reveal-stagger > * { opacity: 0; transform: translateY(24px); transition: opacity .6s cubic-bezier(.2,.8,.2,1), transform .6s cubic-bezier(.2,.8,.2,1); }
        .reveal-stagger.is-visible > *:nth-child(1) { transition-delay: .05s; }
        .reveal-stagger.is-visible > *:nth-child(2) { transition-delay: .15s; }
        .reveal-stagger.is-visible > *:nth-child(3) { transition-delay: .25s; }
        .reveal-stagger.is-visible > *:nth-child(4) { transition-delay: .35s; }
        .reveal-stagger.is-visible > *:nth-child(5) { transition-delay: .45s; }
        .reveal-stagger.is-visible > *:nth-child(6) { transition-delay: .55s; }
        .reveal-stagger.is-visible > * { opacity: 1; transform: translateY(0); }

        /* Navbar shrink-on-scroll */
        .nav-shell { transition: padding .3s ease, box-shadow .3s ease, background-color .3s ease; }
        .nav-shell.scrolled { padding-top: .35rem; padding-bottom: .35rem; box-shadow: 0 6px 20px rgba(15, 23, 42, .06); background-color: rgba(255,255,255,.95); }

        /* Active nav link indicator */
        .nav-link { position: relative; }
        .nav-link::after { content: ''; position: absolute; left: 50%; bottom: -6px; transform: translateX(-50%) scaleX(0); width: 24px; height: 2px; background: #3A86FF; border-radius: 2px; transition: transform .3s; transform-origin: center; }
        .nav-link.active::after, .nav-link:hover::after { transform: translateX(-50%) scaleX(1); }
        .nav-link.active { color: #3A86FF; }

        /* Scroll-to-top */
        .scroll-top { position: fixed; bottom: 24px; right: 24px; width: 46px; height: 46px; border-radius: 50%; background: #3A86FF; color: white; display: flex; align-items: center; justify-content: center; opacity: 0; transform: translateY(12px); transition: opacity .3s, transform .3s, background-color .2s; z-index: 60; box-shadow: 0 10px 24px rgba(58,134,255,.35); cursor: pointer; border: none; }
        .scroll-top.visible { opacity: 1; transform: translateY(0); }
        .scroll-top:hover { background: #2563eb; }

        /* Live pulse */
        .live-dot { position: relative; }
        .live-dot::before { content: ''; position: absolute; inset: 0; border-radius: 50%; background: rgba(74, 222, 128, .55); animation: pulse-ring 1.8s cubic-bezier(.4,0,.6,1) infinite; }

        /* Shine on CTA */
        .cta-shine { background: linear-gradient(110deg, #3A86FF 0%, #4fa0ff 35%, #3A86FF 70%); background-size: 200% auto; animation: shine 4s linear infinite; }

        /* Mobile menu */
        .mobile-menu { transition: transform .3s ease, opacity .3s ease; }
        .mobile-menu.hidden-menu { transform: translateY(-12px); opacity: 0; pointer-events: none; }
    </style>
</head>
<body class="bg-brand-bg font-sans text-gray-700 antialiased">

    {{-- Navbar --}}
    <nav id="main-nav" class="nav-shell bg-white/80 backdrop-blur-md border-b border-gray-100 fixed w-full z-50" x-data="{ open: false }">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="/" class="flex items-center">
                <img src="/images/logo.png" alt="LegalWeb" class="h-10 transition-transform duration-300 hover:scale-105">
            </a>
            <div class="hidden md:flex items-center gap-8 text-sm">
                <a href="#vista-previa" class="nav-link hover:text-brand-light transition">Vista Previa</a>
                <a href="#funcionalidades" class="nav-link hover:text-brand-light transition">Funcionalidades</a>
                <a href="#como-funciona" class="nav-link hover:text-brand-light transition">C&oacute;mo Funciona</a>
                <a href="#planes" class="nav-link hover:text-brand-light transition">Planes</a>
            </div>
            <div class="flex items-center gap-3">
                <a href="/admin/login" class="hidden sm:inline text-sm font-medium text-brand hover:text-brand-light transition">Iniciar Sesi&oacute;n</a>
                <a href="{{ route('auth.google') }}" class="text-sm font-medium bg-brand-light text-white px-5 py-2.5 rounded-lg hover:bg-blue-600 transition shadow-sm hover:shadow-md hover:-translate-y-0.5 transform">
                    Comenzar Gratis
                </a>
                <button @click="open = !open" class="md:hidden p-2 -mr-2 text-brand" aria-label="Men&uacute;">
                    <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L6 6M18 6L18 18M6 6L18 18M18 6L6 18"/></svg>
                </button>
            </div>
        </div>
        {{-- Mobile menu --}}
        <div x-show="open" x-transition.opacity class="md:hidden border-t border-gray-100 bg-white" x-cloak>
            <div class="px-4 py-4 flex flex-col gap-3 text-sm">
                <a href="#vista-previa" @click="open = false" class="py-2 hover:text-brand-light">Vista Previa</a>
                <a href="#funcionalidades" @click="open = false" class="py-2 hover:text-brand-light">Funcionalidades</a>
                <a href="#como-funciona" @click="open = false" class="py-2 hover:text-brand-light">C&oacute;mo Funciona</a>
                <a href="#planes" @click="open = false" class="py-2 hover:text-brand-light">Planes</a>
                <a href="/admin/login" @click="open = false" class="py-2 font-medium text-brand">Iniciar Sesi&oacute;n</a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="hero-gradient pt-28 pb-24 px-4 overflow-hidden relative">
        {{-- Blobs decorativos animados --}}
        <div class="blob blob-1" style="width: 320px; height: 320px; background: #3A86FF; top: 10%; left: -120px;"></div>
        <div class="blob blob-2" style="width: 380px; height: 380px; background: #1E3A5F; bottom: -150px; right: -100px; opacity: 0.25;"></div>
        <div class="blob blob-1" style="width: 220px; height: 220px; background: #93C5FD; top: 40%; right: 10%; opacity: 0.3; animation-delay: -3s;"></div>

        <div class="max-w-6xl mx-auto relative">
            <div class="text-center">
                <div class="inline-flex items-center gap-2 bg-white/80 backdrop-blur text-brand-light text-sm font-medium px-4 py-2 rounded-full mb-6 shadow-sm border border-blue-100 fade-in">
                    <span class="relative flex w-2 h-2">
                        <span class="absolute inline-flex w-full h-full rounded-full bg-green-400 opacity-60 live-dot"></span>
                        <span class="relative inline-flex w-2 h-2 rounded-full bg-green-500"></span>
                    </span>
                    Conectado con la Rama Judicial de Colombia
                </div>
                <h1 class="font-display text-5xl md:text-7xl font-extrabold text-brand leading-tight mb-6 fade-in fade-in-delay-1">
                    Control inteligente de<br>tus procesos <span class="gradient-text">legales</span>
                </h1>
                <p class="text-xl md:text-2xl text-gray-500 max-w-3xl mx-auto mb-10 leading-relaxed fade-in fade-in-delay-2">
                    Importa casos desde la Rama Judicial con un click. Recibe alertas de vencimientos.
                    Genera documentos con IA. Dale visibilidad a tus clientes.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8 fade-in fade-in-delay-3">
                    <a href="{{ route('auth.google') }}" class="cta-shine inline-flex items-center justify-center gap-3 text-white font-semibold px-8 py-4 rounded-xl transition shadow-lg shadow-blue-200 hover:shadow-xl hover:-translate-y-0.5 transform text-lg">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="white" fill-opacity="0.8"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="white" fill-opacity="0.9"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="white" fill-opacity="0.7"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="white" fill-opacity="0.8"/>
                        </svg>
                        Comenzar con Google - Gratis
                    </a>
                    <a href="#rama-judicial" class="inline-flex items-center justify-center gap-2 bg-white text-brand font-semibold px-8 py-4 rounded-xl hover:bg-gray-50 hover:-translate-y-0.5 transform transition border border-gray-200 text-lg group">
                        Ver integraci&oacute;n Rama Judicial
                        <svg class="w-5 h-5 group-hover:translate-y-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </a>
                </div>
                <p class="text-sm text-gray-400">3 casos gratis para siempre. Sin tarjeta de cr&eacute;dito. Configuraci&oacute;n en 60 segundos.</p>
            </div>

            {{-- Stats con contadores animados --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-16 max-w-3xl mx-auto reveal-stagger reveal" data-reveal>
                <div class="bg-white/80 backdrop-blur rounded-2xl p-5 text-center border border-gray-100 shadow-sm hover:shadow-md transition">
                    <div class="text-3xl font-display font-bold text-brand-light" data-counter="21">0</div>
                    <div class="text-xs text-gray-500 mt-1">Flujos procesales</div>
                </div>
                <div class="bg-white/80 backdrop-blur rounded-2xl p-5 text-center border border-gray-100 shadow-sm hover:shadow-md transition">
                    <div class="text-3xl font-display font-bold text-green-500">24/7</div>
                    <div class="text-xs text-gray-500 mt-1">Monitoreo autom&aacute;tico</div>
                </div>
                <div class="bg-white/80 backdrop-blur rounded-2xl p-5 text-center border border-gray-100 shadow-sm hover:shadow-md transition">
                    <div class="text-3xl font-display font-bold text-purple-500">IA</div>
                    <div class="text-xs text-gray-500 mt-1">Asistente jur&iacute;dico</div>
                </div>
                <div class="bg-white/80 backdrop-blur rounded-2xl p-5 text-center border border-gray-100 shadow-sm hover:shadow-md transition">
                    <div class="text-3xl font-display font-bold text-amber-500" data-counter="100" data-suffix="%">0</div>
                    <div class="text-xs text-gray-500 mt-1">Legislaci&oacute;n colombiana</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Vista previa de la plataforma --}}
    <section id="vista-previa" class="py-20 px-4 bg-white">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-12 reveal" data-reveal>
                <h2 class="font-display text-3xl md:text-4xl font-bold text-brand mb-4">Vea la plataforma por dentro</h2>
                <p class="text-gray-500 max-w-2xl mx-auto">As&iacute; se ve LegalWeb cuando gestiona sus casos. Datos reales importados desde la Rama Judicial.</p>
            </div>

            <div x-data="{ active: 0, tabs: [
                { name: 'Dashboard', img: '/images/screenshots/dashboard.png', desc: 'Vista general con KPIs: casos activos, clientes, actuaciones recientes y alertas de vencimiento.' },
                { name: 'Casos', img: '/images/screenshots/casos.png', desc: 'Lista de todos sus casos con importacion directa desde la Rama Judicial. Busqueda y filtros.' },
                { name: 'Detalle del Caso', img: '/images/screenshots/caso-detalle.png', desc: 'Toda la informacion del proceso: datos de Rama Judicial, despacho, juez, sujetos, actuaciones.' },
                { name: 'Agenda', img: '/images/screenshots/agenda.png', desc: 'Recordatorios y vencimientos con alertas automaticas. Plazos calculados en dias habiles.' },
                { name: 'Reportes', img: '/images/screenshots/reportes.png', desc: 'Analitica completa: casos por estado, tipo, prioridad y productividad por abogado.' },
                { name: 'Clientes', img: '/images/screenshots/clientes.png', desc: 'Gestion de clientes con busqueda de procesos en la Rama Judicial por nombre.' }
            ] }">
                {{-- Tabs --}}
                <div class="flex flex-wrap justify-center gap-2 mb-8">
                    <template x-for="(tab, index) in tabs" :key="index">
                        <button @click="active = index"
                            :class="active === index ? 'bg-brand-light text-white shadow-lg shadow-blue-200' : 'bg-white text-gray-600 border border-gray-200 hover:border-brand-light'"
                            class="px-5 py-2.5 rounded-lg text-sm font-medium transition"
                            x-text="tab.name">
                        </button>
                    </template>
                </div>

                {{-- Screenshot con descripcion --}}
                <div class="bg-gray-900 rounded-2xl p-2 shadow-2xl reveal-zoom" data-reveal>
                    <div class="bg-gray-800 rounded-t-xl px-4 py-2 flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-red-400"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                        <div class="w-3 h-3 rounded-full bg-green-400"></div>
                        <span class="text-gray-400 text-xs ml-2">legalweb.com.co/admin</span>
                    </div>
                    <template x-for="(tab, index) in tabs" :key="'img-'+index">
                        <img x-show="active === index"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            :src="tab.img" :alt="tab.name"
                            class="w-full rounded-b-xl">
                    </template>
                </div>

                {{-- Descripcion --}}
                <div class="mt-6 text-center">
                    <template x-for="(tab, index) in tabs" :key="'desc-'+index">
                        <p x-show="active === index" x-transition class="text-gray-500 max-w-xl mx-auto" x-text="tab.desc"></p>
                    </template>
                </div>

                {{-- CTA --}}
                <div class="text-center mt-8">
                    <a href="{{ route('auth.google') }}" class="inline-flex items-center gap-2 bg-brand-light text-white font-semibold px-6 py-3 rounded-xl hover:bg-blue-600 transition shadow-lg shadow-blue-200">
                        Probar gratis - Sin tarjeta de credito
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Integracion Rama Judicial --}}
    <section id="rama-judicial" class="py-20 px-4">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16 reveal" data-reveal>
                <div class="inline-flex items-center gap-2 bg-green-50 text-green-700 text-sm font-medium px-4 py-2 rounded-full mb-4">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                    Exclusivo en Colombia
                </div>
                <h2 class="font-display text-3xl md:text-4xl font-bold text-brand mb-4">Conectado directamente con la Rama Judicial</h2>
                <p class="text-gray-500 max-w-2xl mx-auto">Importe procesos con el n&uacute;mero de radicado. El sistema trae autom&aacute;ticamente toda la informaci&oacute;n: despacho, juez, sujetos procesales y actuaciones.</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 reveal-stagger reveal" data-reveal>
                <div class="bg-gradient-to-br from-blue-50 to-white rounded-2xl p-6 border border-blue-100 card-hover">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-4 feature-icon">
                        <svg class="w-6 h-6 text-brand-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </div>
                    <h3 class="font-semibold text-brand mb-2">Importacion instantanea</h3>
                    <p class="text-sm text-gray-500">Ingrese el radicado y en 2 segundos tiene todo: despacho, juez, partes y actuaciones.</p>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-white rounded-2xl p-6 border border-green-100 card-hover">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mb-4 feature-icon">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </div>
                    <h3 class="font-semibold text-brand mb-2">Sincronizacion diaria</h3>
                    <p class="text-sm text-gray-500">A las 3 AM se consulta la Rama Judicial automaticamente. Reciba email si hay novedades.</p>
                </div>
                <div class="bg-gradient-to-br from-amber-50 to-white rounded-2xl p-6 border border-amber-100 card-hover">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center mb-4 feature-icon">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="font-semibold text-brand mb-2">Alertas inteligentes</h3>
                    <p class="text-sm text-gray-500">Calcula plazos legales en dias habiles (calendario judicial). Auto fija fecha, traslados, sentencias.</p>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-white rounded-2xl p-6 border border-purple-100 card-hover">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-4 feature-icon">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <h3 class="font-semibold text-brand mb-2">Importacion masiva</h3>
                    <p class="text-sm text-gray-500">Pegue hasta 20 radicados y se importan todos automaticamente con reporte detallado.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Funcionalidades --}}
    <section id="funcionalidades" class="py-20 px-4">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16 reveal" data-reveal>
                <h2 class="font-display text-3xl md:text-4xl font-bold text-brand mb-4">Todo lo que necesita para su pr&aacute;ctica legal</h2>
                <p class="text-gray-500 max-w-xl mx-auto">Herramientas dise&ntilde;adas por y para abogados colombianos</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8 reveal-stagger reveal" data-reveal>
                <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm card-hover">
                    <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-5 feature-icon">
                        <svg class="w-6 h-6 text-brand-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">Expediente Digital</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Centraliza documentos, actuaciones y evidencias. Linea de tiempo, facturacion por caso y flujo procesal integrado.</p>
                </div>
                <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm card-hover">
                    <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center mb-5 feature-icon">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">Asistente IA Juridico</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Resumen del caso, siguiente paso recomendado y borradores de demandas, tutelas, memoriales y 10 tipos de documentos.</p>
                </div>
                <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm card-hover">
                    <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center mb-5 feature-icon">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">Portal del Cliente</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Sus clientes consultan el estado de su proceso en tiempo real. Reportes PDF mensuales automaticos opcionales.</p>
                </div>
                <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm card-hover">
                    <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center mb-5 feature-icon">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">21 Flujos Procesales</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Basados en CGP, CPT, Ley 906, CPACA. Plazos en dias habiles con calendario judicial colombiano.</p>
                </div>
                <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm card-hover">
                    <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center mb-5 feature-icon">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">Reportes y Analitica</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Dashboard con KPIs, analitica de despachos, actividad mensual, reporte PDF descargable y envio automatico al cliente.</p>
                </div>
                <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm card-hover">
                    <div class="w-12 h-12 bg-brand/5 rounded-xl flex items-center justify-center mb-5 feature-icon">
                        <svg class="w-6 h-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">Facturacion por Caso</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Registre horas, gastos y conceptos. Controle que es facturable, que ya se cobro y genere cuentas de cobro.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Como funciona --}}
    <section id="como-funciona" class="py-20 px-4 bg-white">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16 reveal" data-reveal>
                <h2 class="font-display text-3xl md:text-4xl font-bold text-brand mb-4">Comience en 3 pasos</h2>
                <p class="text-gray-500">Sin instalaciones. Sin complicaciones. Listo en 60 segundos.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-12 reveal-stagger reveal" data-reveal>
                <div class="text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-100 to-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-sm">
                        <span class="text-3xl font-display font-bold gradient-text">1</span>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">Cree su cuenta</h3>
                    <p class="text-gray-500 text-sm">Registrese con Google en segundos. Cargamos datos de ejemplo para que explore todas las funcionalidades.</p>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-100 to-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-sm">
                        <span class="text-3xl font-display font-bold gradient-text">2</span>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">Importe sus procesos</h3>
                    <p class="text-gray-500 text-sm">Ingrese el radicado de sus casos reales. El sistema importa toda la informacion desde la Rama Judicial.</p>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-100 to-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-sm">
                        <span class="text-3xl font-display font-bold gradient-text">3</span>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">Reciba alertas</h3>
                    <p class="text-gray-500 text-sm">El sistema monitorea sus casos diariamente. Reciba alertas de vencimientos y comparta el portal con sus clientes.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Social proof --}}
    <section class="py-16 px-4 bg-brand/[0.03]">
        <div class="max-w-4xl mx-auto">
            <div class="grid md:grid-cols-3 gap-8 text-center reveal-stagger reveal" data-reveal>
                <div>
                    <div class="text-4xl font-display font-bold text-brand mb-2">Ley 1581</div>
                    <div class="text-sm text-gray-500">Cumplimiento en proteccion de datos personales</div>
                </div>
                <div>
                    <div class="text-4xl font-display font-bold text-brand-light mb-2">Art. 74 CP</div>
                    <div class="text-sm text-gray-500">Secreto profesional garantizado en cada acceso</div>
                </div>
                <div>
                    <div class="text-4xl font-display font-bold text-green-500 mb-2">CGP</div>
                    <div class="text-sm text-gray-500">Flujos basados en legislacion vigente</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Planes --}}
    <section id="planes" class="py-20 px-4">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-10 reveal" data-reveal>
                <h2 class="font-display text-3xl md:text-4xl font-bold text-brand mb-4">Planes que crecen con su firma</h2>
                <p class="text-gray-500 mb-8">Comience gratis. Escale cuando lo necesite.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6 reveal-stagger reveal" data-reveal>
                {{-- Prueba Gratuita --}}
                <div class="bg-white rounded-2xl p-8 border-2 border-green-300 shadow-lg relative card-hover">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-green-500 text-white text-xs font-semibold px-4 py-1 rounded-full">Comience aqui</div>
                    <h3 class="font-semibold text-brand text-xl mb-1">Prueba Gratuita</h3>
                    <p class="text-sm text-gray-500 mb-5">3 meses para explorar todo</p>
                    <div class="mb-6">
                        <span class="text-3xl font-display font-bold text-green-500">$0</span>
                        <span class="text-gray-400 text-sm">/ 3 meses</span>
                    </div>
                    <ul class="space-y-3 text-sm text-gray-600 mb-8">
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> <strong>Casos ilimitados</strong></li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Importacion Rama Judicial</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Asistente IA completo</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Portal del cliente</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Alertas y vencimientos</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Todas las funcionalidades</li>
                    </ul>
                    <a href="{{ route('auth.google') }}" class="block text-center py-3 px-4 rounded-lg bg-green-500 text-white font-medium hover:bg-green-600 transition shadow-lg shadow-green-100">
                        Comenzar gratis
                    </a>
                </div>

                {{-- Pro --}}
                <div class="bg-white rounded-2xl p-8 border-2 border-brand-light shadow-xl relative card-hover">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-brand-light text-white text-xs font-semibold px-4 py-1 rounded-full">Mas popular</div>
                    <h3 class="font-semibold text-brand text-xl mb-1">Pro</h3>
                    <p class="text-sm text-gray-500 mb-5">Para practica profesional</p>
                    <div class="mb-6">
                        <span class="text-3xl font-display font-bold text-brand">Proximamente</span>
                    </div>
                    <ul class="space-y-3 text-sm text-gray-600 mb-8">
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Hasta <strong>20 casos</strong></li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> 3 usuarios</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Todo lo de Prueba Gratuita</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Reportes PDF mensuales</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Facturacion por caso</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Sin limite de tiempo</li>
                    </ul>
                    <a href="{{ route('auth.google') }}" class="block text-center py-3 px-4 rounded-lg bg-brand-light text-white font-medium hover:bg-blue-600 transition shadow-lg shadow-blue-100">
                        Probar gratis primero
                    </a>
                </div>

                {{-- Firma --}}
                <div class="bg-white rounded-2xl p-8 border border-gray-200 shadow-sm card-hover">
                    <h3 class="font-semibold text-brand text-xl mb-1">Firma</h3>
                    <p class="text-sm text-gray-500 mb-5">Para equipos de abogados</p>
                    <div class="mb-6">
                        <span class="text-3xl font-display font-bold text-brand">Proximamente</span>
                    </div>
                    <ul class="space-y-3 text-sm text-gray-600 mb-8">
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Hasta <strong>60 casos</strong></li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> 10 usuarios</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Todo lo del plan Pro</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Permisos por caso</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Importacion masiva</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Soporte prioritario</li>
                    </ul>
                    <a href="{{ route('auth.google') }}" class="block text-center py-3 px-4 rounded-lg border-2 border-brand text-brand font-medium hover:bg-brand hover:text-white transition">
                        Probar gratis primero
                    </a>
                </div>
            </div>
            <p class="text-center text-sm text-gray-400 mt-8">Comience con 3 meses gratis sin limite de casos. Sin tarjeta de credito.</p>
        </div>
    </section>

    {{-- CTA Final --}}
    <section class="py-20 px-4 bg-gradient-to-br from-brand to-blue-900 relative overflow-hidden">
        <div class="blob blob-2" style="width: 400px; height: 400px; background: #3A86FF; top: -150px; left: 50%; transform: translateX(-50%); opacity: 0.18;"></div>
        <div class="max-w-3xl mx-auto text-center relative reveal" data-reveal>
            <img src="/images/logo-square.png" alt="LegalWeb" class="w-20 h-20 mx-auto mb-6 rounded-xl shadow-lg float">
            <h2 class="font-display text-3xl md:text-4xl font-bold text-white mb-4">Modernice su practica legal hoy</h2>
            <p class="text-blue-200 text-lg mb-8 max-w-xl mx-auto">Unase a los abogados colombianos que ya gestionan sus procesos con tecnologia inteligente.</p>
            <a href="{{ route('auth.google') }}" class="inline-flex items-center gap-3 bg-white text-brand font-semibold px-8 py-4 rounded-xl hover:bg-gray-100 transition text-lg shadow-lg">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Comenzar con Google - Es gratis
            </a>
            <p class="text-blue-300/60 text-sm mt-4">Sin tarjeta de credito. Configuracion en 60 segundos.</p>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-white border-t border-gray-100 py-12 px-4">
        <div class="max-w-6xl mx-auto">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                <div>
                    <img src="/images/logo.png" alt="LegalWeb" class="h-8 mb-4">
                    <p class="text-sm text-gray-500">Control inteligente de sus procesos legales. Conectado con la Rama Judicial de Colombia.</p>
                </div>
                <div>
                    <h4 class="font-semibold text-brand text-sm mb-3">Producto</h4>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li><a href="#funcionalidades" class="hover:text-brand-light">Funcionalidades</a></li>
                        <li><a href="#rama-judicial" class="hover:text-brand-light">Rama Judicial</a></li>
                        <li><a href="#planes" class="hover:text-brand-light">Planes</a></li>
                        <li><a href="#como-funciona" class="hover:text-brand-light">Como funciona</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-brand text-sm mb-3">Legal</h4>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li><a href="/portal/terminos" class="hover:text-brand-light">Terminos y Condiciones</a></li>
                        <li><a href="/portal/privacidad" class="hover:text-brand-light">Politica de Privacidad</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-brand text-sm mb-3">Contacto</h4>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li>legalwebco@gmail.com</li>
                        <li>Barrancabermeja, Colombia</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-100 pt-6 flex flex-col md:flex-row justify-between items-center text-sm text-gray-400">
                <p>&copy; {{ date('Y') }} LegalWeb. Todos los derechos reservados.</p>
                <p class="mt-2 md:mt-0">Esta plataforma no sustituye el criterio profesional del abogado.</p>
            </div>
        </div>
    </footer>

    {{-- Scroll to top button --}}
    <button id="scroll-top-btn" class="scroll-top" aria-label="Volver arriba">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
    </button>

    <script>
        (function () {
            // Reveal on scroll
            const revealEls = document.querySelectorAll('[data-reveal]');
            if ('IntersectionObserver' in window && revealEls.length) {
                const io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
                revealEls.forEach(function (el) { io.observe(el); });
            } else {
                revealEls.forEach(function (el) { el.classList.add('is-visible'); });
            }

            // Animated counters
            const counters = document.querySelectorAll('[data-counter]');
            if ('IntersectionObserver' in window && counters.length) {
                const co = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting) return;
                        const el = entry.target;
                        const target = parseInt(el.getAttribute('data-counter'), 10);
                        const suffix = el.getAttribute('data-suffix') || '';
                        const duration = 1400;
                        const start = performance.now();
                        function tick(now) {
                            const elapsed = now - start;
                            const progress = Math.min(elapsed / duration, 1);
                            const eased = 1 - Math.pow(1 - progress, 3);
                            el.textContent = Math.round(target * eased) + suffix;
                            if (progress < 1) requestAnimationFrame(tick);
                        }
                        requestAnimationFrame(tick);
                        co.unobserve(el);
                    });
                }, { threshold: 0.5 });
                counters.forEach(function (el) { co.observe(el); });
            }

            // Navbar shrink + scroll spy + scroll-top button
            const nav = document.getElementById('main-nav');
            const scrollBtn = document.getElementById('scroll-top-btn');
            const sectionIds = ['vista-previa', 'funcionalidades', 'como-funciona', 'planes'];
            const sections = sectionIds.map(function (id) { return document.getElementById(id); }).filter(Boolean);
            const navLinks = document.querySelectorAll('.nav-link');

            function onScroll() {
                const y = window.scrollY;
                if (nav) nav.classList.toggle('scrolled', y > 16);
                if (scrollBtn) scrollBtn.classList.toggle('visible', y > 600);

                // Scroll spy
                let active = null;
                const offset = 140;
                for (const s of sections) {
                    const rect = s.getBoundingClientRect();
                    if (rect.top - offset <= 0 && rect.bottom - offset > 0) { active = s.id; break; }
                }
                navLinks.forEach(function (link) {
                    const href = link.getAttribute('href') || '';
                    link.classList.toggle('active', active && href === '#' + active);
                });
            }
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();

            if (scrollBtn) {
                scrollBtn.addEventListener('click', function () {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }
        })();
    </script>

</body>
</html>
