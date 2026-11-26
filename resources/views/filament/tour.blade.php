@php
    try {
        $isAdminRoot = request()->is('admin') || request()->is('admin/');
        $forced = request()->query('tour') === '1';
        $pending = auth()->check() && empty(auth()->user()->getAttribute('tour_completed_at'));
        $showTour = auth()->check() && $isAdminRoot && ($forced || $pending);
    } catch (\Throwable $e) {
        $showTour = false;
    }
@endphp
<!-- LegalWeb Tour render hook: showTour={{ $showTour ? 'true' : 'false' }} forced={{ $forced ?? false ? 'true' : 'false' }} pending={{ $pending ?? false ? 'true' : 'false' }} adminRoot={{ $isAdminRoot ?? false ? 'true' : 'false' }} auth={{ auth()->check() ? 'true' : 'false' }} -->
@if($showTour)
<link rel="stylesheet" href="{{ asset('vendor/driverjs/driver.css') }}"/>
<script src="{{ asset('vendor/driverjs/driver.js.iife.js') }}"></script>
@verbatim
<script>
console.log('[LegalWeb Tour] script bloque cargado, esperando driver.js...');
(function () {
    function getDriverApi() {
        if (typeof window.driver === 'undefined') return null;
        if (window.driver.js && typeof window.driver.js.driver === 'function') return window.driver.js.driver;
        if (typeof window.driver.driver === 'function') return window.driver.driver;
        return null;
    }
    let attempts = 0;
    function startLegalwebTour() {
        attempts++;
        const driverFn = getDriverApi();
        if (!driverFn) {
            if (attempts > 50) {
                console.error('[LegalWeb Tour] driver.js no se inicializo despues de 10s', { hasGlobal: typeof window.driver, value: window.driver });
                return;
            }
            return setTimeout(startLegalwebTour, 200);
        }
        if (window.__legalwebTourStarted) {
            console.log('[LegalWeb Tour] ya estaba iniciado, abort');
            return;
        }
        window.__legalwebTourStarted = true;
        console.log('[LegalWeb Tour] iniciando tour...');

        const driverObj = driverFn({
        showProgress: true,
        animate: true,
        smoothScroll: true,
        allowClose: true,
        overlayOpacity: 0.7,
        stagePadding: 8,
        stageRadius: 8,
        popoverClass: 'legalweb-tour',
        progressText: 'Paso {{current}} de {{total}}',
        nextBtnText: 'Siguiente &rarr;',
        prevBtnText: '&larr; Anterior',
        doneBtnText: '&iexcl;Listo!',
        steps: [
            {
                popover: {
                    title: 'Bienvenido a LegalWeb &#128075;',
                    description: '<p style="margin:0 0 10px 0;">En menos de 90 segundos le mostramos lo esencial para vigilar sus procesos sin revisarlos uno por uno.</p><p style="margin:0;font-size:13px;opacity:0.85;">Puede cerrar el recorrido cuando quiera y volver a verlo desde <strong>Mi Firma &rarr; Volver a ver tour</strong>.</p>',
                }
            },
            {
                element: '.lw-myday-grid',
                popover: {
                    title: 'Mi d&iacute;a &#128197;',
                    description: '<p style="margin:0;">Lo urgente de hoy en una sola vista: t&eacute;rminos por vencer, agenda y novedades de sus procesos. Empiece aqu&iacute; cada ma&ntilde;ana.</p>',
                    side: 'bottom',
                }
            },
            {
                element: '.fi-global-search-field',
                popover: {
                    title: 'B&uacute;squeda r&aacute;pida &#128269;',
                    description: '<p style="margin:0;">Presione <strong>Ctrl + K</strong> desde cualquier pantalla para encontrar un caso por t&iacute;tulo o radicado, o un cliente por nombre o documento.</p>',
                    side: 'bottom',
                }
            },
            {
                element: 'a[href*="/admin/legal-cases"]',
                popover: {
                    title: 'Casos &#9878;&#65039;',
                    description: '<p style="margin:0 0 8px 0;">Con el bot&oacute;n <strong>&laquo;Importar proceso&raquo;</strong> trae un caso completo con solo el radicado, de la <strong>Rama Judicial</strong> o de <strong>SAMAI</strong>.</p><p style="margin:0;background:rgba(58,134,255,0.15);padding:10px;border-radius:8px;font-size:13px;"><strong>&#128161; &iquest;Tiene muchos procesos?</strong> En <strong>M&aacute;s opciones</strong> puede importarlos desde un <strong>Excel</strong> (descargue la plantilla) o pegar varios radicados a la vez.</p>',
                    side: 'right',
                }
            },
            {
                element: 'a[href*="/admin/buscar-procesos-tyba"]',
                popover: {
                    title: 'Buscar procesos &#128270;',
                    description: '<p style="margin:0;">Un solo campo para buscar por <strong>nombre</strong>, <strong>radicado</strong> o la <strong>c&eacute;dula/NIT de un cliente</strong> en la Rama Judicial y SAMAI a la vez. &Uacute;til para encontrar procesos que a&uacute;n no tiene en LegalWeb.</p>',
                    side: 'right',
                }
            },
            {
                element: 'a[href*="/admin/vencimientos"]',
                popover: {
                    title: 'Vencimientos y t&eacute;rminos &#9200;',
                    description: '<p style="margin:0 0 8px 0;">Los plazos se cuentan en <strong>d&iacute;as h&aacute;biles</strong> con el calendario judicial colombiano: festivos, Semana Santa y vacancia de fin de a&ntilde;o.</p><p style="margin:0;font-size:13px;">Le avisamos <strong>3 d&iacute;as h&aacute;biles antes, 1 d&iacute;a antes y el d&iacute;a del vencimiento</strong>. Para un c&aacute;lculo puntual tiene la <strong>Calculadora de t&eacute;rminos</strong>.</p>',
                    side: 'right',
                }
            },
            {
                popover: {
                    title: 'Vigilancia diaria, sin sorpresas &#128260;',
                    description: '<p style="margin:0 0 8px 0;">Cada madrugada consultamos sus procesos y le avisamos por <strong>correo</strong> y en la <strong>campana &#128276;</strong> cuando hay una actuaci&oacute;n nueva.</p><ul style="margin:0;padding-left:18px;font-size:14px;line-height:1.6;"><li>Si una consulta falla, se lo decimos <strong>ese mismo d&iacute;a</strong> para que revise a mano.</li><li>Cada <strong>lunes</strong> recibe un informe con la fecha de la &uacute;ltima consulta exitosa de cada proceso.</li></ul><p style="margin:8px 0 0 0;font-size:13px;opacity:0.9;">El silencio nunca significa &laquo;sin novedades&raquo;.</p>',
                }
            },
            {
                popover: {
                    title: 'Asistente IA con sus datos protegidos &#129302;',
                    description: '<p style="margin:0 0 10px 0;">En cada caso, el bot&oacute;n <strong>&laquo;Asistente IA&raquo;</strong> resume el expediente, sugiere el pr&oacute;ximo paso y redacta borradores de memoriales, tutelas y demandas.</p><p style="margin:0 0 10px 0;font-size:13px;">Antes de enviar el caso a la IA ocultamos <strong>nombres, documentos, contactos y el radicado</strong>, y los volvemos a poner en su documento.</p><p style="margin:0;background:rgba(255,180,0,0.2);padding:10px;border-radius:8px;font-size:13px;border-left:3px solid #ffb400;"><strong>&#9888;&#65039; Importante:</strong> son <strong>borradores orientativos</strong>; revise siempre antes de usarlos.</p>',
                }
            },
            {
                popover: {
                    title: 'Todo listo para comenzar &#128640;',
                    description: '<p style="margin:0 0 10px 0;">Le sugerimos empezar as&iacute;:</p><ol style="margin:0 0 10px 18px;padding:0;font-size:14px;line-height:1.7;"><li>Entre a <strong>Casos</strong>.</li><li>Pulse <strong>&laquo;Importar proceso&raquo;</strong> y escriba el radicado de su primer proceso.</li><li>&iquest;Muchos procesos? Use <strong>M&aacute;s opciones &rarr; Importar desde Excel</strong>.</li></ol><p style="margin:0;font-size:13px;opacity:0.9;">&iquest;Dudas? Escr&iacute;banos a <strong>legalwebco@gmail.com</strong>.</p>',
                }
            },
        ],
            onDestroyed: () => {
                fetch('/admin/tour/complete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                    },
                }).catch(() => {});
                if (window.location.search.indexOf('tour=1') !== -1) {
                    history.replaceState({}, '', window.location.pathname);
                }
            },
        });

        setTimeout(() => driverObj.drive(), 600);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startLegalwebTour);
    } else {
        startLegalwebTour();
    }
})();
</script>
@endverbatim
<style>
.driver-popover.legalweb-tour {
    background: linear-gradient(135deg, #1E3A5F 0%, #2C4A75 100%);
    color: #fff;
    border-radius: 14px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.08);
    padding: 4px;
    max-width: 440px;
    font-family: Inter, system-ui, -apple-system, sans-serif;
}
.driver-popover.legalweb-tour .driver-popover-title {
    color: #fff;
    font-weight: 700;
    font-size: 18px;
    line-height: 1.3;
    padding: 16px 18px 6px 18px;
    margin: 0;
    letter-spacing: -0.01em;
}
.driver-popover.legalweb-tour .driver-popover-description {
    color: rgba(255,255,255,0.95);
    font-size: 14.5px;
    line-height: 1.55;
    padding: 0 18px 14px 18px;
    margin: 0;
}
.driver-popover.legalweb-tour .driver-popover-description p {
    margin: 0 0 8px 0;
}
.driver-popover.legalweb-tour .driver-popover-footer {
    padding: 12px 18px 16px 18px;
    background: rgba(0,0,0,0.18);
    border-radius: 0 0 12px 12px;
    border-top: 1px solid rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.driver-popover.legalweb-tour .driver-popover-progress-text {
    color: rgba(255,255,255,0.7);
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 0.02em;
}
.driver-popover.legalweb-tour button {
    background: #3A86FF;
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    text-shadow: none;
    transition: all 0.15s ease;
    box-shadow: 0 2px 6px rgba(58,134,255,0.3);
}
.driver-popover.legalweb-tour button:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(58,134,255,0.45);
}
.driver-popover.legalweb-tour .driver-popover-prev-btn {
    background: rgba(255,255,255,0.12);
    box-shadow: none;
}
.driver-popover.legalweb-tour .driver-popover-prev-btn:hover {
    background: rgba(255,255,255,0.22);
    box-shadow: none;
}
.driver-popover.legalweb-tour .driver-popover-close-btn {
    color: rgba(255,255,255,0.7);
    background: transparent;
    box-shadow: none;
    width: 32px;
    height: 32px;
    padding: 0;
    font-size: 22px;
    line-height: 1;
    border-radius: 6px;
    top: 8px;
    right: 8px;
}
.driver-popover.legalweb-tour .driver-popover-close-btn:hover {
    background: rgba(255,255,255,0.12);
    color: #fff;
    transform: none;
}
.driver-popover.legalweb-tour .driver-popover-arrow-side-right.driver-popover-arrow {
    border-right-color: #1E3A5F;
}
.driver-popover.legalweb-tour .driver-popover-arrow-side-left.driver-popover-arrow {
    border-left-color: #2C4A75;
}
.driver-popover.legalweb-tour .driver-popover-arrow-side-top.driver-popover-arrow {
    border-top-color: #2C4A75;
}
.driver-popover.legalweb-tour .driver-popover-arrow-side-bottom.driver-popover-arrow {
    border-bottom-color: #1E3A5F;
}
.driver-popover.legalweb-tour ul, .driver-popover.legalweb-tour ol {
    color: rgba(255,255,255,0.95);
}
.driver-popover.legalweb-tour strong {
    color: #fff;
    font-weight: 700;
}
</style>
@endif
