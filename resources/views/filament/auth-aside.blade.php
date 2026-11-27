{{--
    Pantallas de acceso (entrar, registrarse, recuperar contraseña): panel de marca a la izquierda
    con lo que hace LegalWeb y una muestra de avisos que van llegando; el formulario queda a la derecha.
    Solo para visitantes sin sesion. En pantallas pequeñas el panel se oculta y queda el formulario.
--}}
@guest
<aside class="lw-auth-aside" aria-hidden="true">
    <svg class="lw-auth-aside__arco" viewBox="0 0 120 100" fill="none" stroke-width="10">
        <path d="M30.30 85.70 A42 42 0 0 1 20.23 42.50"/>
        <path d="M21.56 39.08 A42 42 0 0 1 58.17 14.04"/>
        <path d="M61.83 14.04 A42 42 0 0 1 98.44 39.08"/>
        <path d="M99.77 42.50 A42 42 0 0 1 89.70 85.70"/>
    </svg>

    <div class="lw-auth-aside__inner">
        <p class="lw-auth-aside__claim">Sus procesos, revisados todos los días.</p>
        <p class="lw-auth-aside__texto">
            LegalWeb consulta la Rama Judicial y SAMAI por usted, calcula los términos en días hábiles
            y le avisa por correo. Si una consulta falla, también se lo decimos.
        </p>

        <ol class="lw-auth-feed">
            <li class="lw-auth-feed__item">
                <span class="lw-auth-feed__icono lw-auth-feed__icono--azul">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 3.5A1.5 1.5 0 0 1 5.5 2h6.38a1.5 1.5 0 0 1 1.06.44l2.62 2.62a1.5 1.5 0 0 1 .44 1.06v10.38a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 4 16.5v-13Zm3 7.25a.75.75 0 0 0 0 1.5h6a.75.75 0 0 0 0-1.5H7Zm0 3a.75.75 0 0 0 0 1.5h4a.75.75 0 0 0 0-1.5H7Z"/></svg>
                </span>
                <span>
                    <strong>Nueva actuación · Auto admite demanda</strong>
                    <small>Juzgado 003 Civil del Circuito de Bucaramanga · hoy, 6:10 a. m.</small>
                </span>
            </li>
            <li class="lw-auth-feed__item">
                <span class="lw-auth-feed__icono lw-auth-feed__icono--rojo">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .2.08.39.22.53l3 3a.75.75 0 1 0 1.06-1.06l-2.78-2.78V5Z" clip-rule="evenodd"/></svg>
                </span>
                <span>
                    <strong>Vence mañana · Contestación de la demanda</strong>
                    <small>Falta 1 día hábil según el calendario judicial</small>
                </span>
            </li>
            <li class="lw-auth-feed__item">
                <span class="lw-auth-feed__icono lw-auth-feed__icono--verde">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                </span>
                <span>
                    <strong>Consulta a la Rama · 42 procesos al día</strong>
                    <small>Última revisión completa esta madrugada</small>
                </span>
            </li>
        </ol>
    </div>

    <p class="lw-auth-aside__pie">Rama Judicial · SAMAI · Calendario judicial colombiano</p>
</aside>

<style>
    .lw-auth-aside { display: none; }

    @media (min-width: 1024px) {
        .fi-simple-layout { display: grid !important; grid-template-columns: minmax(0, 0.95fr) minmax(0, 1fr); align-items: stretch !important; min-height: 100dvh; }
        .fi-simple-layout > .fi-simple-main-ctn { display: flex; align-items: center; justify-content: center; padding: 3rem 2rem; background: #FFFFFF; }
        .dark .fi-simple-layout > .fi-simple-main-ctn { background: var(--gray-950, #020617); }

        /* Sin la tarjeta generica: el formulario vive sobre la mitad blanca. */
        .fi-simple-layout .fi-simple-main { background: transparent !important; box-shadow: none !important; --tw-ring-shadow: 0 0 #0000 !important; border: 0 !important; padding-left: 0 !important; padding-right: 0 !important; max-width: 25rem !important; width: 100%; }

        .lw-auth-aside {
            position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;
            padding: 3.5rem 3.5rem 2.5rem; background: #1E3A5F; color: #E2E8F0;
        }
        .lw-auth-aside__arco { position: absolute; right: -6rem; bottom: -5rem; width: 30rem; height: auto; opacity: .09; pointer-events: none; }
        .lw-auth-aside__arco path { stroke: #FFFFFF; }
        .lw-auth-aside__arco path:nth-child(n+3) { stroke: #5B9BFF; }

        .lw-auth-aside__inner { position: relative; max-width: 30rem; margin: auto 0; }
        .lw-auth-aside__claim { margin: 0 0 1rem; font-size: 2.1rem; line-height: 1.15; font-weight: 800; letter-spacing: -0.02em; color: #FFFFFF; text-wrap: balance; }
        .lw-auth-aside__texto { margin: 0 0 2.25rem; font-size: 1.02rem; line-height: 1.6; color: #CBD5E1; max-width: 28rem; }

        .lw-auth-feed { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .7rem; }
        .lw-auth-feed__item {
            display: flex; align-items: flex-start; gap: .85rem; padding: .9rem 1rem; border-radius: 12px;
            background: rgba(255, 255, 255, .06); border: 1px solid rgba(255, 255, 255, .1);
            animation: lw-feed-llega .6s cubic-bezier(.16, 1, .3, 1) both;
        }
        .lw-auth-feed__item:nth-child(1) { animation-delay: 1.9s; }
        .lw-auth-feed__item:nth-child(2) { animation-delay: 2.35s; }
        .lw-auth-feed__item:nth-child(3) { animation-delay: 2.8s; }
        .lw-auth-feed__item strong { display: block; font-size: .92rem; font-weight: 600; color: #FFFFFF; }
        .lw-auth-feed__item small { display: block; margin-top: .2rem; font-size: .8rem; color: #B6C3D6; }
        .lw-auth-feed__icono { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 2rem; height: 2rem; border-radius: 8px; }
        .lw-auth-feed__icono svg { width: 1.05rem; height: 1.05rem; }
        .lw-auth-feed__icono--azul { background: rgba(91, 155, 255, .18); color: #8DB8FF; }
        .lw-auth-feed__icono--rojo { background: rgba(248, 113, 113, .18); color: #FCA5A5; }
        .lw-auth-feed__icono--verde { background: rgba(52, 211, 153, .16); color: #6EE7B7; }

        .lw-auth-aside__pie { position: relative; margin: 0; font-size: .8rem; color: #94A3B8; }

        @keyframes lw-feed-llega { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    }

    @media (prefers-reduced-motion: reduce) {
        .lw-auth-feed__item { animation: none !important; }
    }

    /* Celular y tableta: fondo con un toque de marca en lugar del gris plano. */
    @media (max-width: 1023px) {
        .fi-simple-layout { background: linear-gradient(180deg, #EAF1FB 0%, #F8FAFC 45%); }
        .dark .fi-simple-layout { background: none; }
    }
</style>
@endguest
