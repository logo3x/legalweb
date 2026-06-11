<?php

namespace Database\Seeders;

use App\Models\MassEmailTemplate;
use Illuminate\Database\Seeder;

class MassEmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'category' => 'onboarding',
                'name' => 'Bienvenida - primeros pasos',
                'subject' => 'Bienvenido a LegalWeb - tres pasos para empezar',
                'body' => "Hola {{name}}, gracias por unirse a LegalWeb.\n\nPara que pueda sacar el maximo provecho desde el primer dia, le sugerimos tres pasos sencillos:\n\n1. Importe su primer caso desde la Rama Judicial con el numero de radicado. Es la forma mas rapida de empezar.\n\n2. Comparta el portal del cliente desde la vista del caso. Sus clientes podran ver el estado del proceso en tiempo real.\n\n3. Use el Asistente IA dentro de cada caso para generar resumenes, sugerencias de proximos pasos y borradores de tutelas, demandas y memoriales.\n\nSi tiene alguna duda, responda este correo y le ayudamos.",
            ],
            [
                'category' => 'retencion',
                'name' => 'Fin de prueba gratuita - oferta especial',
                'subject' => 'Su prueba gratuita esta por terminar',
                'body' => "Hola {{name}}, su periodo de prueba gratuita en LegalWeb esta por terminar.\n\nPara que pueda seguir gestionando sus casos sin interrupciones, le ofrecemos un 30% de descuento en cualquier plan pagado si activa antes del fin de mes.\n\nIngrese a su panel y vea las opciones en la seccion 'Mejorar plan'.\n\nSi tiene dudas o quiere una demo personalizada, responda este correo y agendamos.",
            ],
            [
                'category' => 'reactivacion',
                'name' => 'Lo extranamos - usuario inactivo',
                'subject' => 'Lo extranamos en LegalWeb',
                'body' => "Hola {{name}}, notamos que hace varias semanas no ingresa a LegalWeb.\n\nQueremos saber si hay algo en lo que podamos ayudarle. Si tuvo algun inconveniente, una funcionalidad que necesitaba pero no encontro, o simplemente requiere apoyo para empezar, respondanos este correo y le acompanamos.\n\nMientras tanto, le recordamos que su cuenta sigue activa con todos sus datos guardados. Puede retomar cuando guste.",
            ],
            [
                'category' => 'encuesta',
                'name' => 'Encuesta rapida - 3 preguntas',
                'subject' => 'Su opinion vale oro para nosotros',
                'body' => "Hola {{name}}, queremos pedirle un favor.\n\nNos gustaria conocer su experiencia con LegalWeb para seguir mejorando. Son solo tres preguntas y le toma menos de dos minutos:\n\n- Que es lo que mas le gusta de la plataforma?\n- Que funcionalidad le hace falta?\n- Recomendaria LegalWeb a otro colega abogado?\n\nResponda este correo con sus impresiones. Las leemos todas.",
            ],
            [
                'category' => 'novedades',
                'name' => 'Novedades del mes - update general',
                'subject' => 'Novedades de LegalWeb este mes',
                'body' => "Hola {{name}}, le compartimos las novedades de este mes en LegalWeb:\n\n- Nueva funcionalidad de busqueda de procesos por nombre directamente en la Rama Judicial, sin necesidad de tener al cliente registrado.\n\n- Notificaciones por correo y campanita mejoradas para vencimientos de terminos procesales.\n\n- Asistente IA con prompts mejorados que reducen riesgo de alucinaciones y devuelven solo informacion verificable.\n\nIngrese al panel para probarlas. Si encuentra algo que se pueda mejorar, escribanos.",
            ],
            [
                'category' => 'marketing',
                'name' => 'Plan firma - para equipos',
                'subject' => 'Pensando en crecer? Conozca nuestro plan Firma',
                'body' => "Hola {{name}}, si esta pensando en crecer su practica o sumar colegas a su equipo, le presentamos nuestro plan Firma:\n\n- Hasta 60 casos activos\n- 10 usuarios con permisos por caso\n- Importacion masiva de procesos desde la Rama Judicial\n- Reportes PDF mensuales para sus clientes\n- Soporte prioritario\n\nSi le interesa o quiere una demo en vivo, responda este correo y agendamos una llamada de 15 minutos.",
            ],
            [
                'category' => 'general',
                'name' => 'Recordatorio termino legal generico',
                'subject' => 'Recordatorio importante para abogados en Colombia',
                'body' => "Hola {{name}}, le recordamos que la Rama Judicial tiene plazos estrictos y los terminos procesales se cuentan en dias habiles segun el calendario judicial.\n\nEn LegalWeb calculamos automaticamente los plazos de sus 21 flujos procesales mas comunes y le enviamos alertas antes de que venzan, para que nunca se le pase un termino.\n\nSi no esta usando esta funcionalidad, le invitamos a explorarla en el modulo Casos > Flujo Procesal.",
            ],

            // Plantillas dirigidas a usuarios inactivos
            [
                'category' => 'reactivacion',
                'name' => 'Inactivo 7 dias - retomar pronto',
                'subject' => 'No deje sus casos solos - retome esta semana',
                'body' => "Hola {{name}}, vemos que hace una semana no ingresa a LegalWeb.\n\nDurante este tiempo es posible que la Rama Judicial haya publicado nuevas actuaciones en sus casos. Recuerde que LegalWeb sincroniza diariamente y le notifica las novedades, pero la informacion esta en su panel esperandolo.\n\nLe invitamos a tomarse 5 minutos para ponerse al dia. Si necesita ayuda para configurar algo o tiene preguntas, responda este correo.\n\nAtentamente,",
            ],
            [
                'category' => 'reactivacion',
                'name' => 'Inactivo 30 dias - lo extranamos',
                'subject' => 'Lo extranamos en LegalWeb',
                'body' => "Hola {{name}}, hace un mes que no ingresa a LegalWeb y queremos saber si todo esta bien.\n\nSu cuenta sigue activa con todos sus datos guardados. Si tuvo algun inconveniente, una funcionalidad que necesitaba pero no encontro, o simplemente perdio el habito, respondanos este correo y le ayudamos a retomar.\n\nMientras tanto, sepa que sus casos siguen siendo sincronizados desde la Rama Judicial cada noche. La informacion esta lista cuando guste retomar.",
            ],
            [
                'category' => 'reactivacion',
                'name' => 'Inactivo 90 dias - antes de archivar',
                'subject' => 'Su cuenta de LegalWeb sigue ahi - una ultima invitacion',
                'body' => "Hola {{name}}, ya han pasado tres meses sin que ingrese a LegalWeb.\n\nQueremos saber si hay algo en lo que podamos ayudarle, o si simplemente la plataforma no fue lo que necesitaba. En cualquier caso, su opinion nos sirve para mejorar.\n\nSi quiere seguir, responda este correo con un 'sigo' y vemos como reactivamos su uso. Si prefiere cerrar la cuenta, basta con que nos lo indique y procesamos su solicitud manteniendo sus datos disponibles si quiere volver.\n\nGracias por haber confiado en nosotros.",
            ],
            [
                'category' => 'onboarding',
                'name' => 'Sin login - nunca entro despues de registrarse',
                'subject' => 'Le ayudamos a empezar con LegalWeb?',
                'body' => "Hola {{name}}, gracias por registrarse en LegalWeb.\n\nNotamos que aun no ha ingresado por primera vez al panel. Sabemos que empezar con una herramienta nueva puede ser intimidante, asi que queremos ofrecerle una mano.\n\nSi quiere, le agendamos una demo personalizada de 15 minutos donde le mostramos como importar su primer caso, configurar el portal del cliente y usar el Asistente IA. Responda este correo con su disponibilidad y coordinamos.\n\nO si prefiere explorar solo, le sugerimos empezar por el tour guiado que aparece la primera vez que ingresa al panel.",
            ],

            // Plantillas para invitar a primer pago (post trial)
            [
                'category' => 'retencion',
                'name' => 'Ultima semana de prueba - convertir',
                'subject' => 'Le quedan pocos dias de prueba gratuita',
                'body' => "Hola {{name}}, su prueba gratuita de LegalWeb termina esta semana.\n\nEn estos meses ha podido ver como la plataforma le ahorra horas de consulta en la Rama Judicial, organiza sus recordatorios y le permite compartir el avance con sus clientes sin pelear con WhatsApp.\n\nPara no perder el acceso ni los datos que ya cargo, active su suscripcion Profesional por 120000 COP/mes desde Mi Plan en el panel. Es un solo pago mensual, sin contratos largos, cancela cuando quiera.\n\nSi tiene alguna duda o quiere conversar antes de decidir, responda este correo y le acompano.",
            ],
            [
                'category' => 'retencion',
                'name' => 'Trial expirado - reactivar',
                'subject' => 'Su prueba ya termino, pero su informacion sigue aqui',
                'body' => "Hola {{name}}, su periodo de prueba en LegalWeb termino la semana pasada.\n\nQueremos avisarle que sus casos, clientes y documentos siguen guardados de forma segura. Si activa la suscripcion Profesional puede retomar exactamente donde lo dejo: 120000 COP/mes, sin perder nada.\n\nIngrese a Mi Plan en el panel para reactivar en 60 segundos. Si decide no continuar, en 30 dias mas archivaremos su cuenta y le avisaremos antes.",
            ],

            // Anuncios de nuevas funcionalidades
            [
                'category' => 'novedades',
                'name' => 'Nueva funcion - busqueda por nombre en Rama Judicial',
                'subject' => 'Ya puede buscar procesos por nombre, sin tener al cliente registrado',
                'body' => "Hola {{name}}, en LegalWeb activamos una nueva funcion que muchos abogados nos pidieron: la busqueda de procesos por nombre directamente en la Rama Judicial.\n\nEntre a Clientes en el panel y use el boton Buscar Procesos del encabezado. Escriba el nombre y le mostramos todos los procesos asociados a esa persona, aunque no la tenga registrada en su firma todavia.\n\nUtil para prospectos, contrapartes o para verificar si una persona tiene procesos antes de aceptar un nuevo caso. Pruebelo y nos cuenta que le parece.",
            ],
            [
                'category' => 'novedades',
                'name' => 'Nueva funcion - alertas de seguridad por login',
                'subject' => 'Ahora le avisamos cada que alguien entra a su cuenta',
                'body' => "Hola {{name}}, agregamos una capa adicional de seguridad a su cuenta de LegalWeb: alertas por correo cada que se inicia sesion.\n\nEl correo le llega con la fecha, hora, IP y navegador del acceso, similar a como lo hacen los bancos. Si reconoce el acceso no necesita hacer nada. Si no, le decimos exactamente que hacer para proteger su cuenta.\n\nLa funcion esta activa por defecto. Puede desactivarla desde Mi Firma > Seguridad de la cuenta si la prefiere apagada. Recomendamos mantenerla activa.",
            ],

            // Marketing referido y comunidad
            [
                'category' => 'marketing',
                'name' => 'Programa de referidos',
                'subject' => 'Recomiende LegalWeb y obtenga un mes gratis',
                'body' => "Hola {{name}}, si LegalWeb le ha sido util, queremos pedirle un favor: cuentele a un colega abogado.\n\nPor cada colega que se registre y active la suscripcion Profesional desde su recomendacion, le regalamos un mes gratis a usted. Sin limite. Si recomienda a tres, son tres meses gratis para usted.\n\nResponda este correo con los correos de los colegas que cree que se pueden beneficiar y nosotros les escribimos directamente mencionando que vienen de su parte. O comparta este correo con ellos directamente.",
            ],
            [
                'category' => 'marketing',
                'name' => 'Plan firma - upgrade equipo',
                'subject' => 'Su firma esta creciendo? Considere el plan Firma',
                'body' => "Hola {{name}}, hemos notado que sus colegas tambien estan usando LegalWeb desde su firma. Cuando una firma trabaja en equipo, vale la pena unificar la gestion.\n\nEl plan Firma incluye permisos por caso (cada abogado solo ve sus casos), centralizacion de facturacion por abogado, reportes consolidados y un solo punto de pago.\n\nResponda este correo si quiere mas detalles o agendar una llamada de 15 minutos donde le mostramos como funciona en vivo.",
            ],

            // Educativo / tips juridicos
            [
                'category' => 'general',
                'name' => 'Tip - como nunca perder un termino procesal',
                'subject' => 'Tres trucos para nunca volver a perder un termino procesal',
                'body' => "Hola {{name}}, los terminos procesales son donde un buen abogado pierde casos por descuido y no por talento. Le compartimos tres trucos para nunca perder uno mas:\n\n1. Configure recordatorios con dos dias de anticipacion, no uno. Asi tiene margen si el dia anterior surge una urgencia.\n\n2. Use el calendario judicial colombiano (no el calendario civil). LegalWeb calcula automaticamente los dias habiles excluyendo vacancia y festivos.\n\n3. Sincronice diariamente con la Rama Judicial. Una actuacion que no detecta a tiempo puede generar un termino que ni siquiera sabia que existia.\n\nEn LegalWeb las tres cosas suceden automaticamente. Si todavia hace alguna de las tres a mano, esta perdiendo tiempo.",
            ],
            [
                'category' => 'general',
                'name' => 'Tip - portal del cliente reduce llamadas',
                'subject' => 'Como bajar 70% las llamadas de clientes preguntando por su caso',
                'body' => "Hola {{name}}, una de las cosas mas pesadas de la practica privada es la cantidad de llamadas de clientes preguntando \"como va mi caso\". Aunque sea legitima, drena su tiempo.\n\nDesde LegalWeb genera un enlace unico por caso que comparte con su cliente. El cliente ve en tiempo real:\n\n- Estado actual del proceso y la siguiente etapa\n- Ultimas actuaciones de la Rama Judicial\n- Documentos que necesita aportar\n- Avance del flujo procesal\n\nLos abogados que ya lo usan reportan una caida del 70% en llamadas de seguimiento. Pruebelo desde la vista del caso, opcion Compartir con Cliente.",
            ],
        ];

        foreach ($templates as $tpl) {
            MassEmailTemplate::firstOrCreate(
                ['name' => $tpl['name']],
                $tpl
            );
        }

        $this->command?->info('MassEmailTemplatesSeeder: '.count($templates).' plantillas cargadas.');
    }
}
