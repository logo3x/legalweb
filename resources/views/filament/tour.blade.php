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
        doneBtnText: '!Listo!',
        steps: [
            {
                popover: {
                    title: '&iexcl;Hola! Bienvenido a LegalWeb &#128075;',
                    description: '<p style="margin:0 0 10px 0;">Antes de que te lances a importar casos como un toro, deja que te muestre las cosas chveres en 90 segundos.</p><p style="margin:0;font-size:13px;opacity:0.85;">Tranquilo, puedes cerrar este tour cuando quieras y volverlo a ver desde <strong>Mi Firma &rarr; Volver a ver tour</strong>. Promesa.</p>',
                }
            },
            {
                element: 'aside nav',
                popover: {
                    title: 'Tu cuartel general &#127960;&#65039;',
                    description: '<p style="margin:0;">Aqui esta TODO lo que necesitas: <strong>Casos, Clientes, Actuaciones, Agenda, Documentos y Reportes</strong>. Es como el menu de tu restaurante favorito, pero legal.</p>',
                    side: 'right',
                }
            },
            {
                element: 'a[href*="/admin/legal-cases"]',
                popover: {
                    title: 'Casos: el corazon del sistema &#10084;&#65039;',
                    description: '<p style="margin:0 0 8px 0;">Aqui creas, importas y gestionas todos tus procesos.</p><p style="margin:0;background:rgba(58,134,255,0.15);padding:10px;border-radius:8px;font-size:13px;"><strong>&#128161; Tip pro:</strong> con el numero de radicado importas un caso desde la <strong>Rama Judicial</strong> en segundos. Tambien puedes hacer importacion masiva.</p>',
                    side: 'right',
                }
            },
            {
                element: 'a[href*="/admin/clients"]',
                popover: {
                    title: 'Clientes: tu agenda dorada &#128178;',
                    description: '<p style="margin:0 0 8px 0;">Tu lista de clientes con toda su info.</p><p style="margin:0;background:rgba(58,134,255,0.15);padding:10px;border-radius:8px;font-size:13px;"><strong>&#128161; Tip pro:</strong> escribe la cedula y trae todos los procesos asociados a esa persona en la Rama Judicial. Magia pura.</p>',
                    side: 'right',
                }
            },
            {
                element: 'a[href*="/admin/reminders"]',
                popover: {
                    title: 'Agenda: nunca mas se te olvida nada &#9200;',
                    description: '<p style="margin:0 0 8px 0;">Recordatorios automaticos generados desde las actuaciones de la Rama Judicial.</p><p style="margin:0;font-size:13px;">Te avisamos por <strong>email</strong> y aqui en la <strong>campanita &#128276;</strong> de arriba a la derecha. Cero excusas para perder un termino.</p>',
                    side: 'right',
                }
            },
            {
                element: 'a[href*="/admin/reports"]',
                popover: {
                    title: 'Reportes: presume con datos &#128202;',
                    description: '<p style="margin:0;">Estadisticas de tu firma: casos por estado, productividad por abogado, analitica de despachos y reportes en PDF listos para descargar y mandar al socio mayoritario.</p>',
                    side: 'right',
                }
            },
            {
                popover: {
                    title: 'Asistente IA en cada caso &#129302;',
                    description: '<p style="margin:0 0 10px 0;">Cuando entres a un caso, veras un boton <strong>"Asistente IA"</strong> que te puede:</p><ul style="margin:0 0 10px 18px;padding:0;font-size:14px;line-height:1.6;"><li>Resumir el caso en 30 segundos</li><li>Sugerir proximos pasos</li><li>Generar borradores de demandas, tutelas y memoriales</li></ul><p style="margin:0;background:rgba(255,180,0,0.2);padding:10px;border-radius:8px;font-size:13px;border-left:3px solid #ffb400;"><strong>&#9888;&#65039; Importante:</strong> son <strong>borradores orientativos</strong>. La IA no reemplaza tu criterio profesional. Siempre revisa antes de firmar.</p>',
                }
            },
            {
                popover: {
                    title: 'Sincronizacion automatica &#128260;',
                    description: '<p style="margin:0 0 8px 0;">Todos los dias a las <strong>3:00 AM</strong>, mientras duermes, consultamos la Rama Judicial por ti.</p><p style="margin:0;font-size:14px;">Si hay novedades, te las contamos por <strong>email</strong> y en la <strong>campanita &#128276;</strong>. Tu solo te enteras cuando despiertas.</p>',
                }
            },
            {
                popover: {
                    title: '!Listo, capitan! &#128640;',
                    description: '<p style="margin:0 0 10px 0;">Hora de empezar. Te recomendamos:</p><ol style="margin:0 0 10px 18px;padding:0;font-size:14px;line-height:1.7;"><li>Ir a <strong>Casos</strong> y darle a <strong>"Importar desde Tyba"</strong></li><li>Pegar tu primer numero de radicado</li><li>Ver la magia ocurrir &#10024;</li></ol><p style="margin:0;font-size:13px;opacity:0.9;">&iquest;Necesitas ayuda? Escribenos a <strong>legalwebco@gmail.com</strong>. Estamos para servirte.</p>',
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
