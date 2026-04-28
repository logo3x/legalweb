@php
    try {
        $showTour = auth()->check()
            && empty(auth()->user()->getAttribute('tour_completed_at'))
            && (request()->is('admin') || request()->is('admin/'));
    } catch (\Throwable $e) {
        $showTour = false;
    }
@endphp
@if($showTour)
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css"/>
<script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof driver === 'undefined') return;

    const driverObj = driver.driver({
        showProgress: true,
        animate: true,
        progressText: 'Paso {{current}} de {{total}}',
        nextBtnText: 'Siguiente',
        prevBtnText: 'Anterior',
        doneBtnText: 'Finalizar',
        steps: [
            {
                popover: {
                    title: 'Bienvenido a LegalWeb',
                    description: 'Le mostraremos rapidamente las funcionalidades principales. Puede cerrar este tour en cualquier momento y volver a verlo desde el menu de usuario.',
                }
            },
            {
                element: 'aside nav',
                popover: {
                    title: 'Menu principal',
                    description: 'Aqui encuentra todos los modulos: Casos, Clientes, Actuaciones, Agenda, Documentos y Reportes.',
                    side: 'right',
                }
            },
            {
                element: 'a[href*="/admin/legal-cases"]',
                popover: {
                    title: 'Casos',
                    description: 'Gestione todos sus procesos. Puede importar directamente desde la Rama Judicial con el numero de radicado, o hacer importacion masiva.',
                    side: 'right',
                }
            },
            {
                element: 'a[href*="/admin/clients"]',
                popover: {
                    title: 'Clientes',
                    description: 'Su agenda de clientes. Puede buscar todos los procesos asociados a un cliente en la Rama Judicial.',
                    side: 'right',
                }
            },
            {
                element: 'a[href*="/admin/reminders"]',
                popover: {
                    title: 'Agenda y Vencimientos',
                    description: 'Recordatorios automaticos generados desde las actuaciones de la Rama Judicial. Recibira alertas por email y notificaciones aqui.',
                    side: 'right',
                }
            },
            {
                element: 'a[href*="/admin/reports"]',
                popover: {
                    title: 'Reportes',
                    description: 'Estadisticas de su firma: casos por estado, productividad por abogado, analitica de despachos y reportes PDF descargables.',
                    side: 'right',
                }
            },
            {
                popover: {
                    title: 'Asistente IA en cada caso',
                    description: 'Al ver un caso, encontrara un boton "Asistente IA" que genera resumenes, sugiere proximos pasos y crea borradores de demandas, tutelas y memoriales. <br><br><strong>IMPORTANTE:</strong> Son borradores orientativos. Siempre debe revisar y verificar antes de usar.',
                }
            },
            {
                popover: {
                    title: 'Sincronizacion automatica',
                    description: 'Cada dia a las 3:00 AM consultamos la Rama Judicial por usted. Si hay nuevas actuaciones, le notificaremos por email y aqui en la campanita.',
                }
            },
            {
                popover: {
                    title: 'Listo para comenzar',
                    description: 'Empiece importando su primer caso desde el modulo "Casos" con el boton "Importar desde Tyba". Si necesita ayuda, escribanos a legalwebco@gmail.com.',
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
        },
    });

    setTimeout(() => driverObj.drive(), 800);
});
</script>
<style>
.driver-popover.driverjs-theme {
    background: #1E3A5F;
    color: #fff;
}
.driver-popover.driverjs-theme .driver-popover-title {
    color: #fff;
    font-weight: 700;
}
.driver-popover.driverjs-theme .driver-popover-description {
    color: rgba(255,255,255,0.9);
}
.driver-popover.driverjs-theme button {
    background: #3A86FF;
    color: #fff;
    border: none;
}
</style>
@endif
