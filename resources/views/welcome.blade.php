<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LegalWeb - Control inteligente de tus procesos legales</title>
    <meta name="description" content="Plataforma SaaS para abogados en Colombia. Gestiona casos, clientes y actuaciones con seguimiento en tiempo real para tus clientes.">
    <script src="https://cdn.tailwindcss.com"></script>
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
</head>
<body class="bg-brand-bg font-sans text-gray-700 antialiased">

    {{-- Navbar --}}
    <nav class="bg-white/80 backdrop-blur-md border-b border-gray-100 fixed w-full z-50">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="/" class="flex items-center">
                <img src="/images/logo.png" alt="LegalWeb" class="h-10">
            </a>
            <div class="hidden md:flex items-center gap-8 text-sm">
                <a href="#funcionalidades" class="hover:text-brand-light transition">Funcionalidades</a>
                <a href="#como-funciona" class="hover:text-brand-light transition">Como Funciona</a>
                <a href="#planes" class="hover:text-brand-light transition">Planes</a>
            </div>
            <div class="flex items-center gap-3">
                <a href="/admin/login" class="text-sm font-medium text-brand hover:text-brand-light transition">Iniciar Sesion</a>
                <a href="{{ route('auth.google') }}" class="text-sm font-medium bg-brand-light text-white px-5 py-2.5 rounded-lg hover:bg-blue-600 transition shadow-sm">
                    Comenzar Gratis
                </a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="pt-28 pb-20 px-4">
        <div class="max-w-6xl mx-auto text-center">
            <div class="inline-flex items-center gap-2 bg-blue-50 text-brand-light text-sm font-medium px-4 py-2 rounded-full mb-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Cumplimiento Ley 1581 de 2012 - Proteccion de Datos
            </div>
            <h1 class="font-display text-5xl md:text-6xl font-extrabold text-brand leading-tight mb-6">
                Control inteligente de<br>tus procesos <span class="text-brand-light">legales</span>
            </h1>
            <p class="text-xl text-gray-500 max-w-2xl mx-auto mb-10 leading-relaxed">
                Gestiona casos, clientes y actuaciones en un solo lugar. Brinda a tus clientes visibilidad en tiempo real del estado de sus procesos.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="{{ route('auth.google') }}" class="inline-flex items-center justify-center gap-2 bg-brand-light text-white font-semibold px-8 py-4 rounded-xl hover:bg-blue-600 transition shadow-lg shadow-blue-200 text-lg">
                    <svg class="w-5 h-5" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="white" fill-opacity="0.8"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="white" fill-opacity="0.9"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="white" fill-opacity="0.7"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="white" fill-opacity="0.8"/>
                    </svg>
                    Comenzar con Google - Gratis
                </a>
                <a href="#como-funciona" class="inline-flex items-center justify-center gap-2 bg-white text-brand font-semibold px-8 py-4 rounded-xl hover:bg-gray-50 transition border border-gray-200 text-lg">
                    Ver como funciona
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </a>
            </div>
            <p class="text-sm text-gray-400">3 casos gratis para siempre. Sin tarjeta de credito.</p>
        </div>
    </section>

    {{-- Funcionalidades --}}
    <section id="funcionalidades" class="py-20 px-4">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="font-display text-3xl md:text-4xl font-bold text-brand mb-4">Todo lo que necesitas para tu practica legal</h2>
                <p class="text-gray-500 max-w-xl mx-auto">Herramientas disenadas por y para abogados colombianos</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm hover:shadow-md transition">
                    <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-brand-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">Expediente Digital</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Centraliza documentos, actuaciones y evidencias de cada caso en un expediente organizado con linea de tiempo.</p>
                </div>
                <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm hover:shadow-md transition">
                    <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">Flujos de Proceso</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">21 flujos basados en la legislacion colombiana (CGP, CPT, Ley 906, CPACA). Completamente editables y personalizables.</p>
                </div>
                <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm hover:shadow-md transition">
                    <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">Portal del Cliente</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Sus clientes consultan el estado de su proceso en tiempo real. Reduzca llamadas y aumente la confianza.</p>
                </div>
                <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm hover:shadow-md transition">
                    <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">Control de Terminos</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Cada paso del flujo tiene plazos definidos. Nunca mas venza un termino por falta de seguimiento.</p>
                </div>
                <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm hover:shadow-md transition">
                    <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">Dashboard Inteligente</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Visualice el estado de su firma con graficos de casos por tipo, estado, actuaciones recientes y mas.</p>
                </div>
                <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm hover:shadow-md transition">
                    <div class="w-12 h-12 bg-brand/5 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">Seguridad y Cumplimiento</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Ley 1581 de 2012, secreto profesional (Art. 74 CP), cifrado de datos, control de accesos y trazabilidad.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Como funciona --}}
    <section id="como-funciona" class="py-20 px-4 bg-white">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="font-display text-3xl md:text-4xl font-bold text-brand mb-4">Comience en 3 pasos</h2>
                <p class="text-gray-500">Sin instalaciones. Sin complicaciones.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-12">
                <div class="text-center">
                    <div class="w-16 h-16 bg-brand-light/10 rounded-2xl flex items-center justify-center mx-auto mb-5">
                        <span class="text-2xl font-display font-bold text-brand-light">1</span>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">Cree su cuenta</h3>
                    <p class="text-gray-500 text-sm">Registrese con Google en segundos. Cargamos datos de ejemplo para que explore la plataforma.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-brand-light/10 rounded-2xl flex items-center justify-center mx-auto mb-5">
                        <span class="text-2xl font-display font-bold text-brand-light">2</span>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">Configure su firma</h3>
                    <p class="text-gray-500 text-sm">Complete los datos de su despacho, suba su logo y personalice los flujos de proceso.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-brand-light/10 rounded-2xl flex items-center justify-center mx-auto mb-5">
                        <span class="text-2xl font-display font-bold text-brand-light">3</span>
                    </div>
                    <h3 class="font-semibold text-brand text-lg mb-2">Gestione sus casos</h3>
                    <p class="text-gray-500 text-sm">Registre casos, asigne flujos, suba documentos y comparta el portal con sus clientes.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Planes --}}
    <section id="planes" class="py-20 px-4" x-data="{ billing: 'monthly' }">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-10">
                <h2 class="font-display text-3xl md:text-4xl font-bold text-brand mb-4">Planes que crecen con su firma</h2>
                <p class="text-gray-500 mb-8">Comience gratis. Escale cuando lo necesite.</p>

                {{-- Switch mensual/semestral --}}
                <div class="inline-flex items-center bg-gray-100 rounded-full p-1">
                    <button @click="billing = 'monthly'" :class="billing === 'monthly' ? 'bg-white shadow text-brand font-semibold' : 'text-gray-500'" class="px-5 py-2 rounded-full text-sm transition">
                        Mensual
                    </button>
                    <button @click="billing = 'biannual'" :class="billing === 'biannual' ? 'bg-white shadow text-brand font-semibold' : 'text-gray-500'" class="px-5 py-2 rounded-full text-sm transition flex items-center gap-1.5">
                        Semestral
                        <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">-17%</span>
                    </button>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                {{-- Gratuito --}}
                <div class="bg-white rounded-2xl p-8 border border-gray-200 shadow-sm">
                    <h3 class="font-semibold text-brand text-xl mb-1">Gratuito</h3>
                    <p class="text-sm text-gray-500 mb-5">Para explorar la plataforma</p>
                    <div class="mb-6">
                        <span class="text-4xl font-display font-bold text-brand">$0</span>
                        <span class="text-gray-400 text-sm">/siempre</span>
                    </div>
                    <ul class="space-y-3 text-sm text-gray-600 mb-8">
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> <strong>3</strong> casos</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> <strong>3</strong> clientes</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> 1 usuario</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Portal del cliente</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> 21 flujos de proceso</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> 50 MB almacenamiento</li>
                        <li class="flex items-center gap-2 text-gray-400"><svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> Notificaciones</li>
                    </ul>
                    <a href="{{ route('auth.google') }}" class="block text-center py-3 px-4 rounded-lg border-2 border-brand text-brand font-medium hover:bg-brand hover:text-white transition">
                        Comenzar gratis
                    </a>
                </div>

                {{-- Profesional --}}
                <div class="bg-white rounded-2xl p-8 border-2 border-brand-light shadow-xl relative">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-brand-light text-white text-xs font-semibold px-4 py-1 rounded-full">Mas popular</div>
                    <h3 class="font-semibold text-brand text-xl mb-1">Profesional</h3>
                    <p class="text-sm text-gray-500 mb-5">Para practica activa</p>
                    <div class="mb-6">
                        <template x-if="billing === 'monthly'">
                            <div>
                                <span class="text-4xl font-display font-bold text-brand">$39.900</span>
                                <span class="text-gray-400 text-sm">/mes</span>
                            </div>
                        </template>
                        <template x-if="billing === 'biannual'">
                            <div>
                                <span class="text-4xl font-display font-bold text-brand">$199.000</span>
                                <span class="text-gray-400 text-sm">/6 meses</span>
                                <p class="text-xs text-green-600 font-medium mt-1">$33.200/mes - Ahorra $40.400</p>
                            </div>
                        </template>
                    </div>
                    <ul class="space-y-3 text-sm text-gray-600 mb-8">
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> <strong>20</strong> casos</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> <strong>20</strong> clientes</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> 3 usuarios</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Portal del cliente</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Notificaciones</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> 1 GB almacenamiento</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> 21 flujos de proceso</li>
                    </ul>
                    <a href="{{ route('auth.google') }}" class="block text-center py-3 px-4 rounded-lg bg-brand-light text-white font-medium hover:bg-blue-600 transition shadow-lg shadow-blue-100">
                        Comenzar prueba gratis
                    </a>
                </div>

                {{-- Firma --}}
                <div class="bg-white rounded-2xl p-8 border border-gray-200 shadow-sm">
                    <h3 class="font-semibold text-brand text-xl mb-1">Firma</h3>
                    <p class="text-sm text-gray-500 mb-5">Para firmas con varios abogados</p>
                    <div class="mb-6">
                        <template x-if="billing === 'monthly'">
                            <div>
                                <span class="text-4xl font-display font-bold text-brand">$69.900</span>
                                <span class="text-gray-400 text-sm">/mes</span>
                            </div>
                        </template>
                        <template x-if="billing === 'biannual'">
                            <div>
                                <span class="text-4xl font-display font-bold text-brand">$349.000</span>
                                <span class="text-gray-400 text-sm">/6 meses</span>
                                <p class="text-xs text-green-600 font-medium mt-1">$58.200/mes - Ahorra $70.400</p>
                            </div>
                        </template>
                    </div>
                    <ul class="space-y-3 text-sm text-gray-600 mb-8">
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> <strong>60</strong> casos</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> <strong>60</strong> clientes</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> 10 usuarios</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Portal del cliente</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Notificaciones</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> 5 GB almacenamiento</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> 21 flujos de proceso</li>
                    </ul>
                    <a href="{{ route('auth.google') }}" class="block text-center py-3 px-4 rounded-lg border-2 border-brand text-brand font-medium hover:bg-brand hover:text-white transition">
                        Comenzar prueba gratis
                    </a>
                </div>
            </div>
            <p class="text-center text-sm text-gray-400 mt-8">Todos los planes incluyen 21 flujos basados en legislacion colombiana. Prueba de 30 dias en planes de pago.</p>
        </div>
    </section>

    {{-- CTA Final --}}
    <section class="py-20 px-4 bg-brand">
        <div class="max-w-3xl mx-auto text-center">
            <img src="/images/logo-square.png" alt="LegalWeb" class="w-20 h-20 mx-auto mb-6 rounded-xl">
            <h2 class="font-display text-3xl md:text-4xl font-bold text-white mb-4">Lleve el control total de su practica legal</h2>
            <p class="text-blue-200 text-lg mb-8">Unase a los abogados colombianos que estan modernizando su ejercicio profesional con LegalWeb.</p>
            <a href="{{ route('auth.google') }}" class="inline-flex items-center gap-2 bg-white text-brand font-semibold px-8 py-4 rounded-xl hover:bg-gray-100 transition text-lg">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Comenzar con Google - Es gratis
            </a>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-white border-t border-gray-100 py-12 px-4">
        <div class="max-w-6xl mx-auto">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                <div>
                    <img src="/images/logo.png" alt="LegalWeb" class="h-8 mb-4">
                    <p class="text-sm text-gray-500">Control inteligente de tus procesos legales.</p>
                </div>
                <div>
                    <h4 class="font-semibold text-brand text-sm mb-3">Producto</h4>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li><a href="#funcionalidades" class="hover:text-brand-light">Funcionalidades</a></li>
                        <li><a href="#planes" class="hover:text-brand-light">Planes</a></li>
                        <li><a href="#como-funciona" class="hover:text-brand-light">Como funciona</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-brand text-sm mb-3">Legal</h4>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li><a href="{{ route('portal.terms') }}" class="hover:text-brand-light">Terminos y Condiciones</a></li>
                        <li><a href="{{ route('portal.privacy') }}" class="hover:text-brand-light">Politica de Privacidad</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-brand text-sm mb-3">Contacto</h4>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li>contacto@legalweb.co</li>
                        <li>Bogota, Colombia</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-100 pt-6 flex flex-col md:flex-row justify-between items-center text-sm text-gray-400">
                <p>&copy; {{ date('Y') }} LegalWeb. Todos los derechos reservados.</p>
                <p class="mt-2 md:mt-0">Esta plataforma no sustituye el criterio profesional del abogado.</p>
            </div>
        </div>
    </footer>

</body>
</html>
