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

    /* Stats overview cards: hover lift sutil */
    .fi-wi-stats-overview-stat {
        transition: transform .3s cubic-bezier(0.16, 1, 0.3, 1),
                    box-shadow .3s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    .fi-wi-stats-overview-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08), 0 2px 6px rgba(15, 23, 42, 0.04) !important;
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

    /* Botones primarios: lift al hover */
    .fi-btn-color-primary {
        transition: transform .2s cubic-bezier(0.25, 1, 0.5, 1),
                    box-shadow .25s cubic-bezier(0.25, 1, 0.5, 1) !important;
    }
    .fi-btn-color-primary:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(58, 134, 255, 0.32) !important;
    }
    .fi-btn-color-primary:active:not(:disabled) {
        transform: translateY(0);
        transition-duration: .1s !important;
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

    @media (prefers-reduced-motion: reduce) {
        .fi-wi, .fi-page-header, .fi-modal-window,
        .fi-sidebar-item-active > .fi-sidebar-item-button::before,
        .fi-topbar-database-notifications-trigger[data-unread]::after,
        .fi-skeleton, .animate-pulse {
            animation: none !important;
        }
        .fi-wi-stats-overview-stat:hover, .fi-btn-color-primary:hover { transform: none !important; }
    }
</style>
