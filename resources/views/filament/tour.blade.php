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
                    description: '<p style="margin:0 0 10px 0;">Perm&iacute;tanos mostrarle las funcionalidades principales en menos de 90 segundos. Le ahorrar&aacute; muchas horas de exploraci&oacute;n.</p><p style="margin:0;font-size:13px;opacity:0.85;">Puede cerrar el recorrido cuando lo desee y volver a verlo desde <strong>Mi Firma &rarr; Volver a ver tour</strong>.</p>',
                }
            },
            {
                element: 'aside nav',
                popover: {
                    title: 'Men&uacute; principal &#127960;&#65039;',
                    description: '<p style="margin:0;">Aqu&iacute; encontrar&aacute; todos los m&oacute;dulos del sistema: <strong>Casos, Clientes, Actuaciones, Agenda, Documentos y Reportes</strong>. Todo lo que necesita en un solo lugar.</p>',
                    side: 'right',
                }
            },
            {
                element: 'a[href*="/admin/legal-cases"]',
                popover: {
                    title: 'Casos: gesti&oacute;n integral de procesos &#9878;&#65039;',
                    description: '<p style="margin:0 0 8px 0;">El coraz&oacute;n del sistema. Aqu&iacute; crea, importa y administra todos sus procesos judiciales.</p><p style="margin:0;background:rgba(58,134,255,0.15);padding:10px;border-radius:8px;font-size:13px;"><strong>&#128161; Sugerencia:</strong> con solo el n&uacute;mero de radicado puede importar un caso completo desde la <strong>Rama Judicial</strong> en segundos. Tambi&eacute;n dispone de importaci&oacute;n masiva.</p>',
                    side: 'right',
                }
            },
            {
                element: 'a[href*="/admin/clients"]',
                popover: {
                    title: 'Clientes: directorio centralizado &#128100;',
                    description: '<p style="margin:0 0 8px 0;">Su base de datos de clientes, con historial completo de procesos asociados.</p><p style="margin:0;background:rgba(58,134,255,0.15);padding:10px;border-radius:8px;font-size:13px;"><strong>&#128161; Sugerencia:</strong> una vez registrado un cliente, en su fila del listado encontrar&aacute; la acci&oacute;n <strong>&laquo;Buscar Procesos&raquo;</strong>, que consulta en la Rama Judicial los procesos asociados al nombre del cliente.</p>',
                    side: 'right',
                }
            },
            {
                element: 'a[href*="/admin/reminders"]',
                popover: {
                    title: 'Agenda y vencimientos &#9200;',
                    description: '<p style="margin:0 0 8px 0;">Recordatorios generados autom&aacute;ticamente a partir de las actuaciones de la Rama Judicial.</p><p style="margin:0;font-size:13px;">Recibir&aacute; notificaciones por <strong>correo electr&oacute;nico</strong> y en la <strong>campana &#128276;</strong> del panel. As&iacute; no se le pasa ning&uacute;n t&eacute;rmino.</p>',
                    side: 'right',
                }
            },
            {
                element: 'a[href*="/admin/reports"]',
                popover: {
                    title: 'Reportes y anal&iacute;tica &#128202;',
                    description: '<p style="margin:0;">Indicadores clave de su firma: casos por estado, productividad por abogado, anal&iacute;tica de despachos y reportes en PDF listos para presentar.</p>',
                    side: 'right',
                }
            },
            {
                popover: {
                    title: 'Asistente IA integrado &#129302;',
                    description: '<p style="margin:0 0 10px 0;">Dentro de cada caso encontrar&aacute; el bot&oacute;n <strong>&laquo;Asistente IA&raquo;</strong>, que le permite:</p><ul style="margin:0 0 10px 18px;padding:0;font-size:14px;line-height:1.6;"><li>Generar res&uacute;menes ejecutivos del expediente</li><li>Sugerir pr&oacute;ximos pasos procesales</li><li>Redactar borradores de demandas, tutelas y memoriales</li></ul><p style="margin:0;background:rgba(255,180,0,0.2);padding:10px;border-radius:8px;font-size:13px;border-left:3px solid #ffb400;"><strong>&#9888;&#65039; Importante:</strong> los textos generados son <strong>borradores orientativos</strong>. La IA no reemplaza el criterio profesional del abogado. Revise siempre antes de utilizarlos.</p>',
                }
            },
            {
                popover: {
                    title: 'Sincronizaci&oacute;n autom&aacute;tica &#128260;',
                    description: '<p style="margin:0 0 8px 0;">Cada d&iacute;a a las <strong>3:00 a.&nbsp;m.</strong> el sistema consulta la Rama Judicial por usted.</p><p style="margin:0;font-size:14px;">Si detecta nuevas actuaciones, le notificaremos por <strong>correo</strong> y en la <strong>campana &#128276;</strong>. Gesti&oacute;n proactiva, sin esfuerzo manual.</p>',
                }
            },
            {
                popover: {
                    title: 'Todo listo para comenzar &#128640;',
                    description: '<p style="margin:0 0 10px 0;">Le sugerimos comenzar de la siguiente manera:</p><ol style="margin:0 0 10px 18px;padding:0;font-size:14px;line-height:1.7;"><li>Ingrese al m&oacute;dulo <strong>Casos</strong></li><li>Seleccione <strong>&laquo;Importar desde Tyba&raquo;</strong></li><li>Indique el n&uacute;mero de radicado de su primer proceso</li></ol><p style="margin:0;font-size:13px;opacity:0.9;">&iquest;Tiene alguna inquietud? Escr&iacute;banos a <strong>legalwebco@gmail.com</strong>. Estaremos atentos.</p>',
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
