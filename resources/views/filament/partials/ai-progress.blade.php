{{--
    Progreso de una tarea de IA dentro de un modal: aparece mientras se envia el formulario.
    La barra avanza rapido al principio y se frena cerca del 90 %: no sabemos cuanto tarda el modelo,
    asi que nunca llega al 100 % hasta que la respuesta vuelve y el modal se cierra.
--}}
@php($pasos = $pasos ?? ['Preparando los datos del caso y ocultando los datos personales…', 'La IA está redactando…', 'Armando el documento…'])
<div
    wire:loading.flex
    wire:target="callMountedAction"
    style="display: none;"
    role="status"
    aria-live="polite"
    data-ai-progress
    x-data="{
        segundos: 0,
        pasos: @js($pasos),
        init() {
            setInterval(() => {
                this.segundos = this.$el.offsetParent === null ? 0 : this.segundos + 1;
            }, 1000);
        },
        get paso() {
            if (this.segundos < 4) return this.pasos[0];
            if (this.segundos < 35) return this.pasos[1];
            return this.pasos[2];
        },
        get avance() {
            return Math.round(8 + 82 * (1 - Math.exp(-this.segundos / 18)));
        },
    }"
    class="lw-ai-progress"
>
    <div style="display: flex; align-items: center; gap: 12px;">
        <span class="lw-ai-progress__icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9.8 3.5 11 7l3.5 1.2L11 9.4 9.8 13 8.6 9.4 5 8.2 8.6 7z"/>
                <path d="M17 12.5l.8 2.2 2.2.8-2.2.8L17 18.5l-.8-2.2-2.2-.8 2.2-.8z"/>
            </svg>
        </span>
        <div style="flex: 1; min-width: 0;">
            <p style="margin: 0; font-size: 14px; font-weight: 600; color: #0F172A;" x-text="paso">{{ $pasos[0] }}</p>
            <p style="margin: 2px 0 0; font-size: 12.5px; color: #475569;">
                <span x-show="segundos < 45">Suele tardar entre 15 y 60 segundos. No cierre esta ventana.</span>
                <span x-show="segundos >= 45" style="display: none;">Está tardando más de lo normal, pero sigue trabajando. No cierre esta ventana.</span>
                <span style="font-variant-numeric: tabular-nums;" x-text="'· ' + segundos + ' s'"></span>
            </p>
        </div>
    </div>
    <div class="lw-ai-progress__track" aria-hidden="true">
        <div class="lw-ai-progress__bar" x-bind:style="'width: ' + avance + '%'"></div>
    </div>
</div>

@once
    <style>
        .lw-ai-progress {
            flex-direction: column;
            gap: 12px;
            margin-top: 4px;
            padding: 14px 16px;
            border: 1px solid #BFDBFE;
            border-radius: 12px;
            background: #EFF6FF;
        }
        .lw-ai-progress__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            border-radius: 999px;
            background: #DBEAFE;
            color: #2563EB;
            animation: lw-ai-pulse 1.6s cubic-bezier(.22, 1, .36, 1) infinite;
        }
        .lw-ai-progress__track {
            position: relative;
            height: 6px;
            overflow: hidden;
            border-radius: 999px;
            background: #DBEAFE;
        }
        .lw-ai-progress__bar {
            position: relative;
            height: 100%;
            width: 8%;
            overflow: hidden;
            border-radius: 999px;
            background: #2563EB;
            transition: width 1s cubic-bezier(.22, 1, .36, 1);
        }
        .lw-ai-progress__bar::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .45), transparent);
            transform: translateX(-100%);
            animation: lw-ai-shine 1.4s ease-in-out infinite;
        }
        @keyframes lw-ai-pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(37, 99, 235, .25); }
            50% { transform: scale(1.06); box-shadow: 0 0 0 6px rgba(37, 99, 235, 0); }
        }
        @keyframes lw-ai-shine {
            to { transform: translateX(100%); }
        }
        .dark .lw-ai-progress { background: rgba(37, 99, 235, .12); border-color: rgba(96, 165, 250, .35); }
        .dark .lw-ai-progress p { color: #E2E8F0 !important; }
        @media (prefers-reduced-motion: reduce) {
            .lw-ai-progress__icon, .lw-ai-progress__bar::after { animation: none; }
            .lw-ai-progress__bar { transition: none; }
        }
    </style>
@endonce
