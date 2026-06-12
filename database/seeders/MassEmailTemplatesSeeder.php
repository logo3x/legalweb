<?php

namespace Database\Seeders;

use App\Models\MassEmailTemplate;
use Illuminate\Database\Seeder;

class MassEmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        // Plantillas a eliminar — mencionan el plan Firma que fue desactivado.
        $obsolete = [
            'Plan firma - para equipos',
            'Plan firma - upgrade equipo',
        ];
        MassEmailTemplate::whereIn('name', $obsolete)->delete();

        $templates = $this->templates();

        foreach ($templates as $tpl) {
            MassEmailTemplate::updateOrCreate(['name' => $tpl['name']], $tpl);
        }

        $this->command?->info('MassEmailTemplatesSeeder: '.count($templates).' plantillas activas, '.count($obsolete).' obsoletas eliminadas.');
    }

    /**
     * Todas las plantillas usan HTML rico que el MassEmailNotification
     * detecta y renderiza dentro de la plantilla emails.mass-campaign.
     *
     * Variables disponibles: {{name}}, {{first_name}}, {{firm}}, {{email}},
     * {{site_url}}, {{login_url}}.
     *
     * Bloques reutilizables (clases CSS ya definidas en mass-campaign.blade.php):
     *   .lw-callout [.success .warning .danger .brand]
     *   .lw-check-list, .lw-num-list
     *   .lw-stat-row > .lw-stat > .lw-stat-num + .lw-stat-lbl
     *   .lw-card > .lw-card-row > .lw-card-ico + .lw-card-body
     *   .lw-cta-block (con boton dentro)
     *   .lw-btn-primary, .lw-btn-outline
     *   .lw-pill [.success .warning .brand]
     *   .lw-divider
     */
    private function templates(): array
    {
        return [
            // ============================================================
            // ONBOARDING
            // ============================================================
            [
                'category' => 'onboarding',
                'name' => 'Bienvenida - primeros pasos',
                'subject' => 'Bienvenido a LegalWeb - 3 pasos para empezar',
                'body' => <<<'HTML'
<p><span class="lw-pill">Bienvenida</span></p>
<h2>Hola {{first_name}}, ya esta dentro.</h2>
<p>Gracias por unirse a <strong>LegalWeb</strong>. Para que saque el maximo provecho desde el primer dia, le proponemos tres pasos sencillos:</p>

<ol class="lw-num-list">
    <li><strong>Importe su primer caso</strong> desde la Rama Judicial con el numero de radicado. Es lo mas rapido para enganchar.</li>
    <li><strong>Comparta el portal del cliente</strong>. Su cliente vera el estado del proceso en tiempo real, sin llamarle.</li>
    <li><strong>Pruebe el Asistente IA</strong>. Resumenes, proximos pasos y borradores de tutelas en segundos.</li>
</ol>

<div class="lw-cta-block">
    <h3>Empieza con tu primer caso</h3>
    <p>Tres meses gratis. Sin tarjeta de credito.</p>
    <a href="{{login_url}}" class="lw-btn">Entrar al panel &rarr;</a>
</div>

<p class="small">PD: si tiene alguna duda, responda este correo. Le contestamos en menos de 24 horas.</p>
HTML,
            ],

            // ============================================================
            // RETENCION / TRIAL
            // ============================================================
            [
                'category' => 'retencion',
                'name' => 'Fin de prueba gratuita - oferta especial',
                'subject' => 'Su prueba esta por terminar - 20% de descuento',
                'body' => <<<'HTML'
<p><span class="lw-pill warning">Su prueba termina pronto</span></p>
<h2>{{first_name}}, no pierda lo que ya construyo.</h2>
<p>Sus casos, clientes, recordatorios y vigilancia automatica de la Rama Judicial siguen activos. Pero solo si activa la suscripcion antes de que termine la prueba.</p>

<div class="lw-callout success">
    <strong>Oferta para usted:</strong> 20% de descuento en su primer mes. Quedaria en <strong>$96.000 COP</strong> (en vez de $120.000), por activarlo antes del fin de mes.
</div>

<p style="text-align:center;margin:28px 0;">
    <a href="{{login_url}}" class="lw-btn-primary">Activar mi suscripcion &rarr;</a>
</p>

<hr class="lw-divider">

<p class="small"><strong>Que pasa si no activa?</strong> Su cuenta entra a modo solo-lectura: puede ver todo pero no crear ni editar. Le damos 30 dias mas para que vuelva — sus datos no se borran.</p>
HTML,
            ],
            [
                'category' => 'retencion',
                'name' => 'Ultima semana de prueba - convertir',
                'subject' => 'Le quedan pocos dias de prueba — no la deje pasar',
                'body' => <<<'HTML'
<p><span class="lw-pill warning">Quedan menos de 7 dias</span></p>
<h2>{{first_name}}, casi se acaba la prueba.</h2>
<p>Esta semana es la ultima para que pueda activar su suscripcion y mantener acceso completo a todo lo que ya configuro en LegalWeb.</p>

<h3>Lo que pierde si no activa:</h3>
<ul class="lw-check-list">
    <li>Vigilancia automatica diaria de la Rama Judicial</li>
    <li>Alertas por correo de nuevas actuaciones</li>
    <li>Asistente IA para resumenes y borradores</li>
    <li>Portal del cliente con seguimiento en tiempo real</li>
    <li>Calculo automatico de terminos en dias habiles</li>
</ul>

<p style="text-align:center;margin:28px 0;">
    <a href="{{login_url}}" class="lw-btn-primary">Activar ahora — $120.000 COP/mes</a>
</p>
<p class="small" style="text-align:center;">Una sola suscripcion. Cancela cuando quieras.</p>
HTML,
            ],
            [
                'category' => 'retencion',
                'name' => 'Trial expirado - reactivar',
                'subject' => 'Su prueba termino, pero sus datos siguen aqui',
                'body' => <<<'HTML'
<p><span class="lw-pill danger">Prueba expirada</span></p>
<h2>{{first_name}}, no se preocupe — todo sigue donde lo dejo.</h2>
<p>Su prueba gratuita termino, pero sus casos, clientes y configuracion <strong>siguen intactos</strong> en su cuenta. Solo entro a modo solo-lectura mientras decide.</p>

<div class="lw-callout">
    <strong>Para reactivar:</strong> entre al panel y vaya a <em>Mi Plan</em>. En menos de 60 segundos vuelve a estar operando al 100%.
</div>

<p style="text-align:center;margin:28px 0;">
    <a href="{{login_url}}" class="lw-btn-primary">Reactivar mi cuenta</a>
</p>

<hr class="lw-divider">

<p class="small">Si la herramienta no fue lo que esperaba, tambien queremos saberlo. Responda este correo y nos dice que faltaba — eso nos ayuda a mejorar.</p>
HTML,
            ],

            // ============================================================
            // INACTIVOS
            // ============================================================
            [
                'category' => 'reactivacion',
                'name' => 'Sin login - nunca entro despues de registrarse',
                'subject' => 'Le ayudamos a dar el primer paso?',
                'body' => <<<'HTML'
<h2>{{first_name}}, vimos que se registro pero no entro todavia.</h2>
<p>Eso suele pasar por una de tres razones, y todas tienen solucion rapida:</p>

<div class="lw-card">
    <div class="lw-card-row">
        <div class="lw-card-ico"><div>1</div></div>
        <div class="lw-card-body">
            <h4>No sabe por donde empezar</h4>
            <p>Entre, pulse "Importar caso" y pegue cualquier radicado. En 30 segundos ve LegalWeb funcionando con un proceso real suyo.</p>
        </div>
    </div>
</div>

<div class="lw-card">
    <div class="lw-card-row">
        <div class="lw-card-ico"><div>2</div></div>
        <div class="lw-card-body">
            <h4>No recuerda como entrar</h4>
            <p>Use el boton de Google con la misma cuenta con la que se registro. No necesita contrasena.</p>
        </div>
    </div>
</div>

<div class="lw-card">
    <div class="lw-card-row">
        <div class="lw-card-ico"><div>3</div></div>
        <div class="lw-card-body">
            <h4>No esta seguro si vale la pena</h4>
            <p>Son 3 meses gratis. Si en una semana no le sirve, no pierde nada. Pero apueste a que si.</p>
        </div>
    </div>
</div>

<p style="text-align:center;margin:30px 0 10px;">
    <a href="{{login_url}}" class="lw-btn-primary">Entrar ahora</a>
</p>
HTML,
            ],
            [
                'category' => 'reactivacion',
                'name' => 'Inactivo 7 dias - retomar pronto',
                'subject' => 'No deje sus casos solos esta semana',
                'body' => <<<'HTML'
<h2>{{first_name}}, ha pasado una semana.</h2>
<p>La Rama Judicial no se detiene los dias que usted no entra. Mientras tanto, LegalWeb ha seguido vigilando sus procesos por usted.</p>

<div class="lw-callout brand">
    Si hubo actuaciones nuevas mientras estuvo fuera, las tiene esperandolo en su bandeja al entrar.
</div>

<p style="text-align:center;margin:28px 0;">
    <a href="{{login_url}}" class="lw-btn-primary">Ver mis novedades</a>
</p>
HTML,
            ],
            [
                'category' => 'reactivacion',
                'name' => 'Inactivo 30 dias - lo extranamos',
                'subject' => 'Lo extranamos en LegalWeb',
                'body' => <<<'HTML'
<h2>{{first_name}}, no lo hemos visto en un mes.</h2>
<p>Esperamos que todo este bien. Solo queremos recordarle que su cuenta sigue activa y sus casos siguen siendo vigilados todas las noches.</p>

<h3>Que paso este mes en LegalWeb:</h3>
<ul class="lw-check-list">
    <li>Mejoramos la velocidad de busqueda en la Rama Judicial</li>
    <li>Nuevos modelos de IA para resumenes mas precisos</li>
    <li>Notificaciones por seguridad cuando alguien entra a su cuenta</li>
</ul>

<p style="text-align:center;margin:28px 0;">
    <a href="{{login_url}}" class="lw-btn-primary">Volver al panel</a>
</p>

<p class="small">Si quiere darse de baja o cambiar el correo, responda y lo hacemos por usted.</p>
HTML,
            ],
            [
                'category' => 'reactivacion',
                'name' => 'Inactivo 90 dias - antes de archivar',
                'subject' => 'Su cuenta sigue ahi — una ultima invitacion',
                'body' => <<<'HTML'
<p><span class="lw-pill warning">Ultima oportunidad</span></p>
<h2>{{first_name}}, han pasado 3 meses.</h2>
<p>Su cuenta sigue activa y sus casos siguen vigilados. Pero queremos ser honestos: en pocas semanas archivaremos cuentas que llevan mas de 120 dias sin uso, para mantener la plataforma rapida.</p>

<div class="lw-callout warning">
    <strong>Antes de archivar:</strong> entre una sola vez al panel y su cuenta vuelve a estado activo automaticamente.
</div>

<p style="text-align:center;margin:28px 0;">
    <a href="{{login_url}}" class="lw-btn-primary">Mantener mi cuenta activa</a>
</p>

<hr class="lw-divider">

<p class="small"><strong>Si LegalWeb no es lo que necesita:</strong> responda este correo con una linea diciendonos por que. Esa retroalimentacion nos ayuda muchisimo.</p>
HTML,
            ],
            [
                'category' => 'reactivacion',
                'name' => 'Lo extranamos - usuario inactivo',
                'subject' => 'Lo extranamos en LegalWeb',
                'body' => <<<'HTML'
<h2>{{first_name}}, han pasado dias sin verle.</h2>
<p>Solo queriamos hacer ping: su cuenta sigue activa, sus procesos siguen siendo vigilados todas las noches y sus clientes siguen pudiendo entrar al portal.</p>

<p style="text-align:center;margin:28px 0;">
    <a href="{{login_url}}" class="lw-btn-primary">Entrar de nuevo</a>
</p>
HTML,
            ],

            // ============================================================
            // INFORMATIVAS / TIPS
            // ============================================================
            [
                'category' => 'general',
                'name' => 'Tip - como nunca perder un termino procesal',
                'subject' => '3 trucos para nunca volver a perder un termino',
                'body' => <<<'HTML'
<p><span class="lw-pill">Tip del experto</span></p>
<h2>Los terminos procesales pierden mas casos que el desconocimiento juridico.</h2>
<p>Tres practicas sencillas que separan a los abogados que pierden terminos de los que nunca lo hacen:</p>

<ol class="lw-num-list">
    <li><strong>Recordatorios con 2 dias de anticipacion</strong>, no uno. Asi tiene margen si el dia anterior surge una urgencia.</li>
    <li><strong>Calendario judicial colombiano</strong>, no el civil. LegalWeb calcula automaticamente excluyendo vacancia y festivos.</li>
    <li><strong>Sincronizacion diaria con la Rama</strong>. Una actuacion que detecte tarde puede generar un termino que ni siquiera sabia que existia.</li>
</ol>

<div class="lw-callout success">
    En LegalWeb las tres cosas pasan automaticamente. Si todavia hace alguna a mano, esta perdiendo tiempo.
</div>

<p style="text-align:center;margin:24px 0;">
    <a href="{{login_url}}" class="lw-btn-outline">Ver mi calendario</a>
</p>
HTML,
            ],
            [
                'category' => 'general',
                'name' => 'Tip - portal del cliente reduce llamadas',
                'subject' => 'Como bajar 70% las llamadas de clientes',
                'body' => <<<'HTML'
<h2>"Doctor, como va mi caso?"</h2>
<p>Si esta pregunta le roba media hora cada manana, hay un atajo: <strong>el portal del cliente</strong>.</p>

<h3>Desde el portal su cliente ve, sin llamarle:</h3>
<ul class="lw-check-list">
    <li>Estado actual del proceso y la siguiente etapa</li>
    <li>Ultimas actuaciones de la Rama Judicial</li>
    <li>Documentos que necesita aportar</li>
    <li>Avance del flujo procesal</li>
</ul>

<div class="lw-stat-row">
    <div class="lw-stat"><span class="lw-stat-num">70%</span><span class="lw-stat-lbl">menos llamadas</span></div>
    <div class="lw-stat"><span class="lw-stat-num">5 seg</span><span class="lw-stat-lbl">generar enlace</span></div>
    <div class="lw-stat"><span class="lw-stat-num">0</span><span class="lw-stat-lbl">password para el cliente</span></div>
</div>

<p style="text-align:center;margin:24px 0;">
    <a href="{{login_url}}" class="lw-btn-primary">Activar portal en un caso</a>
</p>
HTML,
            ],
            [
                'category' => 'general',
                'name' => 'Recordatorio termino legal generico',
                'subject' => 'Recordatorio importante para abogados en Colombia',
                'body' => <<<'HTML'
<p><span class="lw-pill warning">Recordatorio</span></p>
<h2>{{first_name}}, verifique sus terminos esta semana.</h2>
<p>Buen momento para repasar manualmente sus procesos en curso:</p>

<ul class="lw-check-list">
    <li>Terminos que vencen en los proximos 7 dias</li>
    <li>Memoriales pendientes de presentar</li>
    <li>Actuaciones recientes sin revisar</li>
    <li>Recordatorios programados que no recibio (por si hubo algun fallo)</li>
</ul>

<p style="text-align:center;margin:24px 0;">
    <a href="{{login_url}}" class="lw-btn-outline">Ver mi calendario</a>
</p>
HTML,
            ],
            [
                'category' => 'general',
                'name' => 'Encuesta rapida - 3 preguntas',
                'subject' => 'Su opinion vale oro para nosotros',
                'body' => <<<'HTML'
<h2>{{first_name}}, 3 preguntas y le invitamos un cafe.</h2>
<p>Estamos construyendo LegalWeb pensando en lo que un abogado realmente necesita. Pero sin escucharle a usted, vamos a ciegas.</p>

<p>Si nos regala 90 segundos respondiendo este correo con su opinion sobre las siguientes preguntas, le mandamos un codigo para un cafe gratis:</p>

<ol class="lw-num-list">
    <li>Que tarea repetitiva en su practica le quita mas tiempo cada semana?</li>
    <li>Que funcion de LegalWeb le ha sorprendido para bien?</li>
    <li>Que le falta a LegalWeb para ser indispensable?</li>
</ol>

<div class="lw-callout">
    Conteste a este correo, en una linea por pregunta basta. Le respondemos personalmente con su codigo de cafe.
</div>
HTML,
            ],

            // ============================================================
            // NOVEDADES / PRODUCTO
            // ============================================================
            [
                'category' => 'novedades',
                'name' => 'Novedades del mes - update general',
                'subject' => 'Novedades de LegalWeb este mes',
                'body' => <<<'HTML'
<p><span class="lw-pill brand">Changelog mensual</span></p>
<h2>Lo que mejoramos este mes en LegalWeb</h2>
<p>Algunas mejoras notables que ya estan disponibles en su panel:</p>

<div class="lw-card">
    <div class="lw-card-row">
        <div class="lw-card-ico"><div style="background:#D1FAE5;color:#065F46;">+</div></div>
        <div class="lw-card-body">
            <h4>Busqueda por nombre o cedula en la Rama</h4>
            <p>Ya no necesita el radicado: escriba el nombre del cliente o su cedula y vea todos sus procesos. Importacion en un click.</p>
        </div>
    </div>
</div>

<div class="lw-card">
    <div class="lw-card-row">
        <div class="lw-card-ico"><div style="background:#DBEAFE;color:#1E40AF;">&#9889;</div></div>
        <div class="lw-card-body">
            <h4>Modelos de IA mas precisos</h4>
            <p>Cambiamos a la ultima generacion de modelos para que sus resumenes y borradores tengan mejor calidad y mas contexto colombiano.</p>
        </div>
    </div>
</div>

<div class="lw-card">
    <div class="lw-card-row">
        <div class="lw-card-ico"><div style="background:#FEF3C7;color:#78350F;">&#128274;</div></div>
        <div class="lw-card-body">
            <h4>Alertas de seguridad por login</h4>
            <p>Le avisamos por correo cada vez que alguien entra a su cuenta. Si no fue usted, sabra al instante.</p>
        </div>
    </div>
</div>

<p style="text-align:center;margin:28px 0;">
    <a href="{{login_url}}" class="lw-btn-primary">Probarlas en mi panel</a>
</p>
HTML,
            ],
            [
                'category' => 'novedades',
                'name' => 'Nueva funcion - busqueda por nombre en Rama Judicial',
                'subject' => 'Ya puede buscar procesos por nombre o cedula',
                'body' => <<<'HTML'
<p><span class="lw-pill brand">Nueva funcion</span></p>
<h2>Busque procesos sin tener el radicado.</h2>
<p>Hasta hoy, importar un caso requeria saber su numero de radicacion. Eso cambio:</p>

<div class="lw-callout success">
    <strong>Ahora puede buscar en la Rama Judicial por:</strong> nombre del cliente, razon social, cedula o NIT — directamente desde el panel.
</div>

<h3>Asi funciona en 3 pasos:</h3>
<ol class="lw-num-list">
    <li>Entre a <em>Buscar en Rama Judicial</em> en el menu lateral</li>
    <li>Elija si busca por nombre, radicado o documento</li>
    <li>Pulse el boton "Copiar radicado" en el resultado que le interesa e importelo como caso</li>
</ol>

<p style="text-align:center;margin:28px 0;">
    <a href="{{login_url}}" class="lw-btn-primary">Probar la busqueda</a>
</p>
HTML,
            ],
            [
                'category' => 'novedades',
                'name' => 'Nueva funcion - alertas de seguridad por login',
                'subject' => 'Ahora le avisamos cada inicio de sesion',
                'body' => <<<'HTML'
<p><span class="lw-pill brand">Nueva funcion</span></p>
<h2>Su cuenta avisa cuando alguien entra.</h2>
<p>Como hacen los bancos. Cada vez que alguien inicie sesion en su cuenta de LegalWeb le llegara un correo inmediato con:</p>

<ul class="lw-check-list">
    <li>Fecha y hora del acceso</li>
    <li>Direccion IP y ciudad aproximada</li>
    <li>Navegador y sistema operativo</li>
</ul>

<div class="lw-callout warning">
    Si reconoce el inicio de sesion, ignore el correo. Si <strong>no fue usted</strong>, contestelo y desactivamos accesos en menos de una hora.
</div>

<p>Es una capa extra de seguridad sin que tenga que hacer nada — ya esta activa.</p>
HTML,
            ],
            [
                'category' => 'novedades',
                'name' => 'Nueva integracion disponible',
                'subject' => 'Acceso rapido a la Rama Judicial desde el dashboard',
                'body' => <<<'HTML'
<p><span class="lw-pill brand">Acceso rapido</span></p>
<h2>{{first_name}}, ahora busca en la Rama sin salir del panel.</h2>
<p>Agregamos un widget de busqueda directa en el dashboard. Sin tener que abrir nada, busque cualquier proceso y traigalo a su sistema.</p>

<div class="lw-card">
    <div class="lw-card-row">
        <div class="lw-card-ico"><div>&#128269;</div></div>
        <div class="lw-card-body">
            <h4>Desde la portada del panel</h4>
            <p>Una caja de busqueda con tres modos: nombre, radicado o cedula/NIT. Resultado inmediato.</p>
        </div>
    </div>
</div>

<p style="text-align:center;margin:28px 0;">
    <a href="{{login_url}}" class="lw-btn-primary">Ver el dashboard</a>
</p>
HTML,
            ],

            // ============================================================
            // MARKETING / PROMO / CASOS DE EXITO
            // ============================================================
            [
                'category' => 'promo',
                'name' => 'Suscripcion anual con descuento',
                'subject' => 'Suscripcion anual: 2 meses gratis',
                'body' => <<<'HTML'
<p><span class="lw-pill success">Plan anual</span></p>
<h2>Pague 10 meses, use 12.</h2>
<p>Si quiere comprometerse con LegalWeb por todo el ano, tenemos un trato:</p>

<div class="lw-stat-row">
    <div class="lw-stat"><span class="lw-stat-num">$1.200K</span><span class="lw-stat-lbl">si paga mensual</span></div>
    <div class="lw-stat" style="background:#1E3A5F;"><span class="lw-stat-num" style="color:#fff;">$1.000K</span><span class="lw-stat-lbl" style="color:#BFDBFE;">plan anual</span></div>
    <div class="lw-stat"><span class="lw-stat-num">$200K</span><span class="lw-stat-lbl">ahorro al ano</span></div>
</div>

<p style="text-align:center;">El equivalente a <strong>dos meses gratis</strong>, pagando una sola vez.</p>

<p style="text-align:center;margin:28px 0;">
    <a href="{{login_url}}" class="lw-btn-primary">Activar plan anual</a>
</p>

<p class="small" style="text-align:center;">Cancelacion en cualquier momento. Si cancela antes del ano, reembolsamos los meses no usados.</p>
HTML,
            ],
            [
                'category' => 'promo',
                'name' => 'Programa de referidos',
                'subject' => 'Recomiende LegalWeb y los dos ganan un mes gratis',
                'body' => <<<'HTML'
<p><span class="lw-pill">Programa de referidos</span></p>
<h2>Si LegalWeb le sirvio, ayudenos a llegar a mas colegas.</h2>
<p>Por cada abogado que se suscriba con su recomendacion, ambos reciben <strong>un mes gratis</strong> automaticamente.</p>

<h3>Como funciona:</h3>
<ol class="lw-num-list">
    <li>Envie a su colega el enlace <a href="{{site_url}}">legalweb.com.co</a> con el codigo que aparecera en su panel.</li>
    <li>Su colega se registra usando ese codigo.</li>
    <li>Cuando active suscripcion paga, los dos reciben <strong>un mes adicional gratis</strong> en su siguiente cobro.</li>
</ol>

<div class="lw-callout success">
    No hay limite. Si recomienda a 10 colegas y todos activan, son <strong>10 meses gratis</strong> para usted. Es como si pagara la suscripcion anual pero la usara casi dos anos.
</div>

<p style="text-align:center;margin:28px 0;">
    <a href="{{login_url}}" class="lw-btn-primary">Ver mi codigo de referido</a>
</p>
HTML,
            ],
            [
                'category' => 'promo',
                'name' => 'Black Friday - oferta limitada',
                'subject' => 'Black Friday LegalWeb: 50% de descuento',
                'body' => <<<'HTML'
<p><span class="lw-pill warning">Solo por 72 horas</span></p>
<h2>$60.000 COP el primer mes — la mitad.</h2>
<p>Una sola vez al ano. Este Black Friday queremos que pruebe LegalWeb por la mitad de su precio.</p>

<div class="lw-cta-block">
    <h3>50% off el primer mes</h3>
    <p>Use el codigo <strong>BLACK50</strong> al activar la suscripcion.</p>
    <a href="{{login_url}}" class="lw-btn">Activar con descuento &rarr;</a>
</div>

<h3>Lo que incluye, igual:</h3>
<ul class="lw-check-list">
    <li>Vigilancia automatica de procesos en la Rama Judicial</li>
    <li>Alertas por correo de cada actuacion nueva</li>
    <li>Asistente IA juridico (resumenes, borradores)</li>
    <li>Portal del cliente</li>
    <li>21 flujos procesales + calculo de terminos</li>
</ul>

<p class="small" style="text-align:center;">La oferta termina en 72 horas. Despues vuelve al precio normal de $120.000/mes.</p>
HTML,
            ],
            [
                'category' => 'promo',
                'name' => 'Caso de exito - cliente real',
                'subject' => 'Como Dr. Ramirez recupero 14 horas a la semana',
                'body' => <<<'HTML'
<p><span class="lw-pill">Caso real</span></p>
<h2>"Estaba perdiendo 2 horas diarias entrando a la Rama uno por uno."</h2>
<p>Dr. Carlos Ramirez maneja 38 procesos en cinco juzgados de Bucaramanga. Cada manana abria la Rama, copiaba el radicado del primero, esperaba el cargue, revisaba, cerraba, y empezaba con el siguiente.</p>

<div class="lw-callout brand">
    "El primer dia con LegalWeb, abri el panel a las 7am y ya tenia tres notificaciones de actuaciones del dia anterior. Sin haber tocado nada."
</div>

<div class="lw-stat-row">
    <div class="lw-stat"><span class="lw-stat-num">14 h</span><span class="lw-stat-lbl">por semana</span></div>
    <div class="lw-stat"><span class="lw-stat-num">38</span><span class="lw-stat-lbl">procesos vigilados</span></div>
    <div class="lw-stat"><span class="lw-stat-num">0</span><span class="lw-stat-lbl">terminos perdidos</span></div>
</div>

<p>El Dr. Ramirez ya cumplio 6 meses con LegalWeb. Dice que el primer ahorro fue tiempo, pero el segundo — el que mas valora — es el sueno tranquilo de saber que nada se le va por debajo del radar.</p>

<p style="text-align:center;margin:28px 0;">
    <a href="{{login_url}}" class="lw-btn-primary">Probarlo en mi practica</a>
</p>
HTML,
            ],
            [
                'category' => 'promo',
                'name' => 'Webinar gratuito - IA juridica',
                'subject' => 'Webinar gratuito: IA juridica aplicada al dia a dia',
                'body' => <<<'HTML'
<p><span class="lw-pill brand">Evento gratuito</span></p>
<h2>"IA juridica que de verdad ahorra tiempo (sin sustituirle)"</h2>
<p>Le invitamos a un webinar de 45 minutos donde mostramos casos reales de como abogados estan usando IA para:</p>

<ul class="lw-check-list">
    <li>Resumir expedientes de 200 paginas en 15 segundos</li>
    <li>Generar borradores de tutelas listas para revisar</li>
    <li>Identificar puntos debiles en escritos del contrario</li>
    <li>Calcular probabilidades de exito segun jurisprudencia</li>
</ul>

<div class="lw-cta-block">
    <h3>Proximo webinar</h3>
    <p>Jueves 7:00 PM (hora Colombia) &middot; 45 minutos &middot; preguntas en vivo</p>
    <a href="{{site_url}}" class="lw-btn">Reservar mi cupo gratis</a>
</div>

<p class="small">Se graba para los que no puedan asistir en vivo. Reservando llegas la grabacion al correo.</p>
HTML,
            ],

            // ============================================================
            // ESTACIONALES
            // ============================================================
            [
                'category' => 'estacional',
                'name' => 'Fin de ano - balance y proximo ano',
                'subject' => 'Fin de ano: cierre con orden, empiece tranquilo',
                'body' => <<<'HTML'
<p><span class="lw-pill">Cierre de ano</span></p>
<h2>{{first_name}}, antes de que cierre la Rama, ordene su despacho.</h2>
<p>La vacancia judicial empieza en pocos dias. Buen momento para hacer tres cosas que dejaran su 2027 mas tranquilo:</p>

<ol class="lw-num-list">
    <li><strong>Revise procesos sin actualizar</strong>. Filtre por "ultima actuacion hace mas de 60 dias" y verifique que no se le este olvidando algo.</li>
    <li><strong>Genere los reportes anuales</strong>. Cuantos casos cerrados, ingresos por cliente, productividad por flujo procesal.</li>
    <li><strong>Programe recordatorios de enero</strong>. Los primeros 15 dias de enero son criticos — deje todo lo importante anotado ya.</li>
</ol>

<p style="text-align:center;margin:28px 0;">
    <a href="{{login_url}}" class="lw-btn-primary">Ir al panel</a>
</p>

<p class="small">Gracias por confiar en LegalWeb este ano. Que el 2027 le traiga muchos casos ganados.</p>
HTML,
            ],
            [
                'category' => 'estacional',
                'name' => 'Vuelta de vacancia judicial',
                'subject' => 'Volvio la Rama — ponganos al dia en su panel',
                'body' => <<<'HTML'
<p><span class="lw-pill success">Termino la vacancia</span></p>
<h2>{{first_name}}, ya esta la Rama abierta de nuevo.</h2>
<p>Los despachos retoman actividad esta semana. LegalWeb ya esta sincronizando con la Rama, y todas las actuaciones nuevas le llegaran al correo como siempre.</p>

<div class="lw-callout">
    <strong>Pro tip:</strong> entre al panel hoy y revise los recordatorios de los proximos 15 dias. Los primeros dias post-vacancia suelen acumular muchos terminos a la vez.
</div>

<p style="text-align:center;margin:28px 0;">
    <a href="{{login_url}}" class="lw-btn-primary">Revisar mi calendario</a>
</p>
HTML,
            ],
            [
                'category' => 'estacional',
                'name' => 'Cumple de LegalWeb',
                'subject' => 'LegalWeb cumple un ano — gracias por estar',
                'body' => <<<'HTML'
<p><span class="lw-pill brand">Aniversario</span></p>
<h2>Un ano de LegalWeb, gracias a usted.</h2>
<p>Hace 12 meses LegalWeb era una idea. Hoy es la herramienta de cientos de abogados en Colombia que vigilan miles de procesos cada noche.</p>

<div class="lw-stat-row">
    <div class="lw-stat"><span class="lw-stat-num">12</span><span class="lw-stat-lbl">meses</span></div>
    <div class="lw-stat"><span class="lw-stat-num">8K+</span><span class="lw-stat-lbl">procesos vigilados</span></div>
    <div class="lw-stat"><span class="lw-stat-num">450K+</span><span class="lw-stat-lbl">actuaciones notificadas</span></div>
</div>

<p>Para celebrar, este mes le regalamos <strong>2 meses gratis</strong> en su proximo cobro como agradecimiento por haber estado desde temprano.</p>

<div class="lw-callout success">
    No tiene que hacer nada — el beneficio se aplica solo en su siguiente factura.
</div>

<p style="text-align:center;margin:28px 0;">
    <a href="{{login_url}}" class="lw-btn-outline">Ver mi cuenta</a>
</p>
HTML,
            ],

            // ============================================================
            // ENCUESTA POST-USO
            // ============================================================
            [
                'category' => 'general',
                'name' => 'Encuesta post-mes-de-uso',
                'subject' => 'Llego al mes con LegalWeb — como le fue?',
                'body' => <<<'HTML'
<h2>{{first_name}}, hoy cumple un mes en LegalWeb.</h2>
<p>Si llego hasta aca es porque algo le sirvio. Queremos saber que.</p>

<p>Conteste este correo con su impresion sobre estas tres preguntas — 30 segundos basta:</p>

<ol class="lw-num-list">
    <li>De 1 a 10, cuanto recomendaria LegalWeb a un colega?</li>
    <li>Que funcion ha usado mas?</li>
    <li>Que funcion echaria de menos si manana desapareciera?</li>
</ol>

<div class="lw-callout">
    Le leemos personalmente. Cada respuesta entra a una lista compartida con el equipo de producto que decide que mejoramos cada mes.
</div>
HTML,
            ],
        ];
    }
}
