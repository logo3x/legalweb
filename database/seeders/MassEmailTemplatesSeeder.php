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
