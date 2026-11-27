{{--
    Marca de LegalWeb dibujada en SVG: una balanza de la justicia dentro de un arco de vigilancia
    por tramos, en azul marino y azul de marca. En las pantallas de acceso (.fi-simple-header) se
    arma en secuencia: el arco se dibuja, la balanza se levanta, los platos se mecen hasta quedar en
    equilibrio y el punto de alerta cae y late una vez. Sin animacion si el sistema pide reducir movimiento.

    El alto lo da el contenedor del logo de Filament (brandLogoHeight); en el acceso se agranda.
--}}
<span class="lw-marca" role="img" aria-label="LegalWeb">
    <svg class="lw-marca-emblema" viewBox="0 0 120 100" aria-hidden="true" focusable="false">
        <g class="lw-marca-arco" fill="none" stroke-width="10" stroke-linecap="butt">
            <path class="lw-marca-tramo lw-marca-marino" pathLength="1" d="M30.30 85.70 A42 42 0 0 1 20.23 42.50"/>
            <path class="lw-marca-tramo lw-marca-marino" pathLength="1" d="M21.56 39.08 A42 42 0 0 1 58.17 14.04"/>
            <path class="lw-marca-tramo lw-marca-azul" pathLength="1" d="M61.83 14.04 A42 42 0 0 1 98.44 39.08"/>
            <path class="lw-marca-tramo lw-marca-azul" pathLength="1" d="M99.77 42.50 A42 42 0 0 1 89.70 85.70"/>
        </g>

        <g class="lw-marca-balanza">
            <rect class="lw-marca-base lw-marca-marino-f" x="43" y="77" width="34" height="7" rx="3.5"/>
            <rect class="lw-marca-poste lw-marca-marino-f" x="56.5" y="38" width="7" height="41" rx="3"/>
            <rect class="lw-marca-brazo lw-marca-marino-f" x="33" y="37" width="54" height="6.5" rx="3.25"/>

            <g class="lw-marca-plato lw-marca-plato-izq">
                <path class="lw-marca-marino" fill="none" stroke-width="2.6" stroke-linecap="round" d="M40 43 L31.5 60 M40 43 L48.5 60"/>
                <path class="lw-marca-marino-f" d="M29 59.5 H51 A11 9.5 0 0 1 29 59.5 Z"/>
            </g>
            <g class="lw-marca-plato lw-marca-plato-der">
                <path class="lw-marca-marino" fill="none" stroke-width="2.6" stroke-linecap="round" d="M80 43 L71.5 60 M80 43 L88.5 60"/>
                <path class="lw-marca-azul-f" d="M69 59.5 H91 A11 9.5 0 0 1 69 59.5 Z"/>
            </g>

            <circle class="lw-marca-pulso" cx="60" cy="29" r="5.5"/>
            <circle class="lw-marca-punto lw-marca-azul-f" cx="60" cy="29" r="5.5"/>
        </g>
    </svg>

    <span class="lw-marca-nombre"><span class="lw-marca-legal">LEGAL</span><span class="lw-marca-web">WEB</span></span>
</span>

@once
<style>
    .lw-marca { --lw-marino: #1E3A5F; --lw-azul: #2F7BF5; display: inline-flex; align-items: center; gap: .5rem; height: 100%; white-space: nowrap; line-height: 1; }
    .dark .lw-marca { --lw-marino: #9DB8DC; --lw-azul: #5B9BFF; }
    .lw-marca-emblema { height: 100%; width: auto; flex-shrink: 0; overflow: visible; }
    .lw-marca-arco .lw-marca-marino, .lw-marca-plato .lw-marca-marino { stroke: var(--lw-marino); }
    .lw-marca-arco .lw-marca-azul { stroke: var(--lw-azul); }
    .lw-marca-marino-f { fill: var(--lw-marino); }
    .lw-marca-azul-f { fill: var(--lw-azul); }
    .lw-marca-pulso { fill: none; stroke: var(--lw-azul); stroke-width: 2; opacity: 0; }
    .lw-marca-nombre { font-family: Inter, ui-sans-serif, system-ui, sans-serif; font-size: 1.15rem; font-weight: 800; letter-spacing: -0.01em; }
    .lw-marca-legal { color: var(--lw-marino); }
    .lw-marca-web { color: var(--lw-azul); }

    /* Pantallas de acceso: marca grande. La animacion corre dentro de .lw-anima (acceso y landing). */
    .fi-simple-header .fi-logo { height: 4.25rem !important; }
    .fi-simple-header .lw-marca { gap: .75rem; }
    .fi-simple-header .lw-marca-nombre { font-size: 2.05rem; }

    :is(.fi-simple-header, .lw-anima) .lw-marca-tramo { stroke-dasharray: 1; animation: lw-dibuja-tramo .42s cubic-bezier(.65, 0, .35, 1) both; }
    :is(.fi-simple-header, .lw-anima) .lw-marca-tramo:nth-child(1) { animation-delay: .2s; }
    :is(.fi-simple-header, .lw-anima) .lw-marca-tramo:nth-child(2) { animation-delay: .33s; }
    :is(.fi-simple-header, .lw-anima) .lw-marca-tramo:nth-child(3) { animation-delay: .46s; }
    :is(.fi-simple-header, .lw-anima) .lw-marca-tramo:nth-child(4) { animation-delay: .59s; }

    :is(.fi-simple-header, .lw-anima) .lw-marca-base { transform-box: fill-box; transform-origin: 50% 50%; animation: lw-ensancha .4s cubic-bezier(.16, 1, .3, 1) .55s both; }
    :is(.fi-simple-header, .lw-anima) .lw-marca-poste { transform-box: fill-box; transform-origin: 50% 100%; animation: lw-levanta .45s cubic-bezier(.16, 1, .3, 1) .7s both; }
    :is(.fi-simple-header, .lw-anima) .lw-marca-brazo { transform-box: fill-box; transform-origin: 50% 50%; animation: lw-ensancha .45s cubic-bezier(.16, 1, .3, 1) .95s both; }
    :is(.fi-simple-header, .lw-anima) .lw-marca-plato { transform-box: view-box; animation: lw-cuelga .5s cubic-bezier(.16, 1, .3, 1) 1.15s both, lw-equilibrio 1.3s cubic-bezier(.37, 0, .63, 1) 1.6s both; }
    :is(.fi-simple-header, .lw-anima) .lw-marca-plato-izq { transform-origin: 40px 43px; }
    :is(.fi-simple-header, .lw-anima) .lw-marca-plato-der { transform-origin: 80px 43px; animation-name: lw-cuelga, lw-equilibrio-der; }
    :is(.fi-simple-header, .lw-anima) .lw-marca-punto { transform-box: fill-box; transform-origin: 50% 50%; animation: lw-cae-punto .45s cubic-bezier(.16, 1, .3, 1) 1.5s both; }
    :is(.fi-simple-header, .lw-anima) .lw-marca-pulso { transform-box: fill-box; transform-origin: 50% 50%; animation: lw-late 1.1s cubic-bezier(.16, 1, .3, 1) 2s both; }
    :is(.fi-simple-header, .lw-anima) .lw-marca-nombre { animation: lw-aparece .5s cubic-bezier(.16, 1, .3, 1) .4s both; }

    @keyframes lw-dibuja-tramo { from { stroke-dashoffset: 1; } to { stroke-dashoffset: 0; } }
    @keyframes lw-ensancha { from { transform: scaleX(0); } to { transform: scaleX(1); } }
    @keyframes lw-levanta { from { transform: scaleY(0); } to { transform: scaleY(1); } }
    /* translate y rotate van en propiedades separadas para que la caida y el vaiven no se pisen. */
    @keyframes lw-cuelga { from { opacity: 0; translate: 0 -8px; } to { opacity: 1; translate: 0 0; } }
    @keyframes lw-equilibrio { 0% { rotate: 0deg; } 25% { rotate: 7deg; } 55% { rotate: -4deg; } 80% { rotate: 1.5deg; } 100% { rotate: 0deg; } }
    @keyframes lw-equilibrio-der { 0% { rotate: 0deg; } 25% { rotate: -7deg; } 55% { rotate: 4deg; } 80% { rotate: -1.5deg; } 100% { rotate: 0deg; } }
    @keyframes lw-cae-punto { from { opacity: 0; transform: translateY(-14px) scale(.6); } to { opacity: 1; transform: translateY(0) scale(1); } }
    @keyframes lw-late { 0% { opacity: .9; transform: scale(1); } 100% { opacity: 0; transform: scale(2.6); } }
    @keyframes lw-aparece { from { opacity: 0; transform: translateX(-6px); } to { opacity: 1; transform: translateX(0); } }

    @media (prefers-reduced-motion: reduce) {
        :is(.fi-simple-header, .lw-anima) .lw-marca * { animation: none !important; }
    }
</style>
@endonce
