{{-- Estilos de pulido para el panel admin: motion, jerarquia, micro-interacciones --}}
<style>
    /* Curves premium */
    .fi-section, .fi-wi, .fi-card, .fi-btn, .fi-link,
    .fi-ta-row, .fi-input, .fi-select-input {
        --ease-out-expo: cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* Widgets del dashboard: fade-up al cargar (CSS animation, no JS) */
    .fi-wi {
        animation: lwAdminFadeUp .55s cubic-bezier(0.16, 1, 0.3, 1) both;
    }
    .fi-wi:nth-child(1) { animation-delay: 0ms; }
    .fi-wi:nth-child(2) { animation-delay: 60ms; }
    .fi-wi:nth-child(3) { animation-delay: 120ms; }
    .fi-wi:nth-child(4) { animation-delay: 180ms; }
    .fi-wi:nth-child(5) { animation-delay: 240ms; }
    .fi-wi:nth-child(6) { animation-delay: 300ms; }

    @keyframes lwAdminFadeUp {
        from { opacity: 0; transform: translate3d(0, 10px, 0); }
        to   { opacity: 1; transform: translate3d(0, 0, 0); }
    }

    /* Stats overview cards: hover lift sutil (solo pointer fino) */
    .fi-wi-stats-overview-stat {
        transition: transform .3s cubic-bezier(0.23, 1, 0.32, 1),
                    box-shadow .3s cubic-bezier(0.23, 1, 0.32, 1) !important;
    }
    @media (hover: hover) and (pointer: fine) {
        .fi-wi-stats-overview-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08), 0 2px 6px rgba(15, 23, 42, 0.04) !important;
        }
    }

    /* Stat value: numero grande con tabular nums para alineacion */
    .fi-wi-stats-overview-stat-value {
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.025em !important;
    }

    /* Tablas: row hover suave */
    .fi-ta-row {
        transition: background .15s ease;
    }

    /* Botones (todos): Emil pattern — release 200ms, press 120ms con scale(.97) */
    .fi-btn {
        transition: transform .2s cubic-bezier(0.23, 1, 0.32, 1),
                    box-shadow .25s cubic-bezier(0.23, 1, 0.32, 1),
                    background-color .15s ease !important;
    }
    /* Press feedback en TODO boton */
    .fi-btn:active:not(:disabled) {
        transform: scale(.97);
        transition-duration: .12s !important;
    }
    /* Hover lift solo en pointer fino */
    @media (hover: hover) and (pointer: fine) {
        .fi-btn-color-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(58, 134, 255, 0.32) !important;
        }
    }
    /* En touch, press deja el feedback principal */
    .fi-btn-color-primary:active:not(:disabled) {
        transform: scale(.97);
        box-shadow: 0 4px 10px rgba(58, 134, 255, 0.22) !important;
    }

    /* Sidebar item: indicador izquierdo en el activo */
    .fi-sidebar-item-active > .fi-sidebar-item-button {
        position: relative;
    }
    .fi-sidebar-item-active > .fi-sidebar-item-button::before {
        content: '';
        position: absolute;
        left: 0; top: 8px; bottom: 8px;
        width: 3px;
        background: #3A86FF;
        border-radius: 0 3px 3px 0;
        animation: lwSidebarMark .35s cubic-bezier(0.16, 1, 0.3, 1) both;
    }
    @keyframes lwSidebarMark {
        from { transform: scaleY(0); opacity: 0; }
        to   { transform: scaleY(1); opacity: 1; }
    }

    /* Topbar: sombra elevada al hacer scroll */
    .fi-topbar {
        transition: box-shadow .25s ease;
    }

    /* Notificaciones (campana): pulse al recibir nueva */
    .fi-topbar-database-notifications-trigger[data-unread] {
        position: relative;
    }
    .fi-topbar-database-notifications-trigger[data-unread]::after {
        content: '';
        position: absolute;
        top: 6px; right: 6px;
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #EF4444;
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6);
        animation: lwBellPulse 2s infinite cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes lwBellPulse {
        0%   { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.55); }
        70%  { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
        100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }

    /* Inputs: focus ring premium */
    .fi-input:focus,
    .fi-select-input:focus,
    textarea.fi-input:focus {
        box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.18) !important;
        border-color: #3A86FF !important;
        transition: box-shadow .15s ease, border-color .15s ease;
    }

    /* Page header: pulldown en mount */
    .fi-page-header {
        animation: lwAdminFadeUp .5s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    /* Modal entrance refinado */
    .fi-modal-window {
        animation: lwModalIn .35s cubic-bezier(0.16, 1, 0.3, 1) both !important;
    }
    @keyframes lwModalIn {
        from { opacity: 0; transform: translate3d(0, 14px, 0) scale(.98); }
        to   { opacity: 1; transform: translate3d(0, 0, 0) scale(1); }
    }

    /* Skeleton shimmer en loading states */
    .fi-skeleton, .animate-pulse {
        background: linear-gradient(90deg, #F1F5F9 0%, #E2E8F0 50%, #F1F5F9 100%) !important;
        background-size: 200% 100% !important;
        animation: lwShimmer 1.4s linear infinite !important;
    }
    @keyframes lwShimmer {
        0%   { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* ============================================
       TOAST STACKING premium (Emil/Sonner pattern)
       Cuando hay multiples notificaciones, se apilan
       con scale .94 + offset 14px (no se cubren).
       ============================================ */
    .fi-no.fi-no-position-top-right .fi-no-list,
    .fi-no.fi-no-position-bottom-right .fi-no-list {
        perspective: 1500px;
    }
    .fi-no-notification {
        transition: transform .35s cubic-bezier(0.23, 1, 0.32, 1),
                    opacity .25s cubic-bezier(0.23, 1, 0.32, 1) !important;
        transform-origin: bottom center;
    }
    /* Stack: cada notificacion encima de la anterior se hace mas pequena */
    .fi-no-notification:nth-last-child(2) { transform: translateY(-14px) scale(.96); opacity: .85; }
    .fi-no-notification:nth-last-child(3) { transform: translateY(-28px) scale(.92); opacity: .55; }
    .fi-no-notification:nth-last-child(n+4) { transform: translateY(-42px) scale(.88); opacity: 0; }

    /* Toast entrance: cae desde arriba con curva iOS */
    .fi-no-notification.fi-no-notification-entering {
        animation: lwToastIn .42s cubic-bezier(0.32, 0.72, 0, 1) both;
    }
    @keyframes lwToastIn {
        from { opacity: 0; transform: translate3d(0, -24px, 0) scale(.94); }
        to   { opacity: 1; transform: translate3d(0, 0, 0) scale(1); }
    }

    /* ============================================
       Number ticker: target visual de count-up
       ============================================ */
    [data-lw-ticker] {
        font-variant-numeric: tabular-nums;
        display: inline-block;
        transition: opacity .2s ease;
    }
    [data-lw-ticker].is-counting { opacity: .92; }

    /* ============================================
       Tabs en pages: indicador con clip-path (sin layout shift)
       ============================================ */
    .fi-tabs-item {
        position: relative;
        transition: color .2s cubic-bezier(0.23, 1, 0.32, 1) !important;
    }
    .fi-tabs-item[aria-selected="true"]::after {
        content: '';
        position: absolute;
        left: 12px; right: 12px; bottom: 0;
        height: 2px;
        background: #3A86FF;
        border-radius: 2px 2px 0 0;
        animation: lwTabIndicator .35s cubic-bezier(0.23, 1, 0.32, 1) both;
    }
    @keyframes lwTabIndicator {
        from { transform: scaleX(0); opacity: 0; }
        to   { transform: scaleX(1); opacity: 1; }
    }

    /* ============================================
       Inputs entering / autocomplete suggestions:
       @starting-style para fade-in al aparecer
       ============================================ */
    @supports (transition-behavior: allow-discrete) {
        .fi-dropdown-panel {
            transition: opacity .18s cubic-bezier(0.23, 1, 0.32, 1),
                        transform .22s cubic-bezier(0.23, 1, 0.32, 1),
                        display .22s allow-discrete;
        }
        @starting-style {
            .fi-dropdown-panel {
                opacity: 0;
                transform: translateY(-6px) scale(.985);
            }
        }
    }

    /* ============================================
       Hover gap-fill: el dropdown del usuario en el
       topbar tiene un gap que rompe el hover. Lo
       relleno con un pseudo-elemento invisible.
       ============================================ */
    .fi-topbar-user-menu {
        position: relative;
    }
    .fi-topbar-user-menu::before {
        content: '';
        position: absolute;
        top: 100%;
        left: 0; right: 0;
        height: 8px;
        z-index: 1;
    }

    @media (prefers-reduced-motion: reduce) {
        .fi-wi, .fi-page-header, .fi-modal-window,
        .fi-sidebar-item-active > .fi-sidebar-item-button::before,
        .fi-topbar-database-notifications-trigger[data-unread]::after,
        .fi-skeleton, .animate-pulse,
        .fi-no-notification.fi-no-notification-entering,
        .fi-tabs-item[aria-selected="true"]::after {
            animation: none !important;
        }
        .fi-wi-stats-overview-stat:hover, .fi-btn-color-primary:hover { transform: none !important; }
        .fi-no-notification:nth-last-child(n+2) { transform: none; opacity: 1; }
    }
</style>

<script>
/* ============================================
   Number ticker: cuenta de 0 al valor final con
   curva ease-out-expo. Target: cualquier nodo con
   [data-lw-ticker] cuyo textContent inicial es el
   valor final (o data-target).
   ============================================ */
(function () {
    if (window.lwTickerInit) return;
    window.lwTickerInit = true;

    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function easeOutExpo(t) { return t === 1 ? 1 : 1 - Math.pow(2, -10 * t); }

    function parseNumber(text) {
        // Soporta "1.234", "1,234", "12.345.678", "120.000" y separadores varios
        const cleaned = (text || '').replace(/[^\d-]/g, '');
        return parseInt(cleaned, 10) || 0;
    }

    function formatLikeOriginal(value, original) {
        // Si el original tenia puntos como miles, los mantenemos
        const useDot = /\.\d{3}/.test(original) || /\d\.\d{3}/.test(original);
        const useComma = !useDot && /,\d{3}/.test(original);
        const sep = useDot ? '.' : (useComma ? ',' : '');
        if (!sep) return String(value);
        return value.toLocaleString('es-CO').replace(/[.,]/g, sep);
    }

    function tick(el) {
        if (el.dataset.lwTickerDone) return;
        const original = el.textContent.trim();
        const target = parseInt(el.dataset.target || original, 10);
        if (!Number.isFinite(target) || target === 0) { el.dataset.lwTickerDone = '1'; return; }
        if (reduce) { el.textContent = formatLikeOriginal(target, original); el.dataset.lwTickerDone = '1'; return; }

        el.dataset.lwTickerDone = '1';
        el.classList.add('is-counting');
        const start = performance.now();
        const duration = Math.min(1100, 600 + Math.log10(Math.max(target, 10)) * 180);

        function frame(now) {
            const t = Math.min(1, (now - start) / duration);
            const v = Math.round(target * easeOutExpo(t));
            el.textContent = formatLikeOriginal(v, original);
            if (t < 1) requestAnimationFrame(frame);
            else el.classList.remove('is-counting');
        }
        requestAnimationFrame(frame);
    }

    function observeTickers() {
        // Auto-marcado: numeros grandes en stat values del panel
        document.querySelectorAll('.fi-wi-stats-overview-stat-value:not([data-lw-ticker])').forEach((el) => {
            const txt = el.textContent.trim();
            // Solo si parece un numero puro o con separadores y >= 10
            if (/^[\d.,\s]+$/.test(txt) && parseNumber(txt) >= 10) {
                el.dataset.lwTicker = '';
                el.dataset.target = parseNumber(txt);
            }
        });

        const targets = document.querySelectorAll('[data-lw-ticker]:not([data-lw-ticker-done])');
        if (!targets.length) return;

        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        tick(entry.target);
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.4 });
            targets.forEach((el) => io.observe(el));
        } else {
            targets.forEach(tick);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', observeTickers);
    } else {
        observeTickers();
    }

    // Livewire: re-observar despues de navegaciones SPA-like
    document.addEventListener('livewire:navigated', () => setTimeout(observeTickers, 50));
    document.addEventListener('livewire:load', () => setTimeout(observeTickers, 50));
})();
</script>
