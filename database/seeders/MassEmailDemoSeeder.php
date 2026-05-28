<?php

namespace Database\Seeders;

use App\Models\MassEmailCampaign;
use App\Models\User;
use Illuminate\Database\Seeder;

class MassEmailDemoSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::where('role', 'superadmin')->first()
            ?? User::orderBy('id')->first();

        if (! $superadmin) {
            $this->command?->info('Sin usuarios en la base. Saltando MassEmailDemoSeeder.');

            return;
        }

        if (MassEmailCampaign::count() > 0) {
            $this->command?->info('Ya hay campanas registradas. No se cargan demos.');

            return;
        }

        // Campania 1: borrador a todos
        MassEmailCampaign::create([
            'user_id' => $superadmin->id,
            'subject' => 'Bienvenido a LegalWeb - tips para sacar el maximo provecho',
            'body' => "Hola {{name}}, gracias por unirse a LegalWeb.\n\nQueremos compartirle algunos tips para sacar el maximo provecho de la plataforma desde el primer dia:\n\n1. Importe sus casos desde la Rama Judicial usando el numero de radicado - es la forma mas rapida de empezar.\n\n2. Comparta el portal del cliente para que vea el estado de su caso en tiempo real. Aumenta la confianza y reduce las llamadas de seguimiento.\n\n3. Use el Asistente IA en cada caso para generar resumenes y borradores de tutelas, demandas y memoriales.\n\nSi tiene alguna pregunta, responda este correo y le ayudamos.\n\n",
            'audience_type' => 'all',
            'status' => 'borrador',
        ]);

        // Campania 2: programada para manana, audiencia por estado
        MassEmailCampaign::create([
            'user_id' => $superadmin->id,
            'subject' => 'Su prueba gratuita esta por terminar - ofertas especiales',
            'body' => "Hola {{name}}, su periodo de prueba gratuita en LegalWeb esta por terminar.\n\nPara que pueda seguir gestionando sus casos sin interrupciones, le ofrecemos un 30% de descuento en cualquier plan pagado si activa antes del fin de mes.\n\nIngrese al panel y vea las opciones en 'Mejorar plan'.\n\nSi tiene dudas o quiere una demo personalizada, responda este correo.\n\n",
            'audience_type' => 'by_status',
            'audience_filters' => ['statuses' => ['activo', 'prospecto']],
            'status' => 'programado',
            'scheduled_at' => now()->addDay()->setHour(10)->setMinute(0),
        ]);

        // Campania 3: borrador a usuarios especificos (los primeros 2 que existan distintos del superadmin)
        $specificUsers = User::where('id', '!=', $superadmin->id)
            ->orderBy('id')
            ->limit(2)
            ->pluck('id')
            ->toArray();

        if (! empty($specificUsers)) {
            MassEmailCampaign::create([
                'user_id' => $superadmin->id,
                'subject' => 'Encuesta rapida - 3 preguntas sobre su experiencia',
                'body' => "Hola {{name}}, queremos pedirle un favor.\n\nNos gustaria conocer su experiencia con LegalWeb para seguir mejorando. Son solo 3 preguntas y le toma menos de 2 minutos:\n\n- Que es lo que mas le gusta de la plataforma?\n- Que funcionalidad le hace falta?\n- Recomendaria LegalWeb a otro colega?\n\nResponda este correo con sus impresiones. Su opinion vale oro para nosotros.\n\n",
                'audience_type' => 'specific',
                'audience_user_ids' => $specificUsers,
                'status' => 'borrador',
            ]);
        }

        $this->command?->info('MassEmailDemoSeeder: 3 campanas de ejemplo creadas.');
    }
}
