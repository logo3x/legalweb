<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los limites del plan Gratuito pasan del 12 de diciembre de 2026 al 1 de febrero de 2027
 * (FirmPlan::FREE_LIMITS_FROM). Las firmas que ya recibieron el aviso de 30 dias con la fecha
 * anterior lo vuelven a recibir con la fecha correcta, y las plantillas de correo dejan de
 * prometer la fecha vieja y la vigilancia por cedula o NIT (la Rama ya no la permite).
 */
return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const TEXT_FIXES = [
        '12 de diciembre de 2026' => '1 de febrero de 2027',
        '<h4>Vigilancia de clientes por cedula o NIT</h4>' => '<h4>Vigilancia de sus clientes en la Rama Judicial</h4>',
        'Cada noche buscamos a sus clientes en la Rama Judicial y le avisamos si aparecen en un proceso nuevo.' => 'Cada noche buscamos a sus clientes por su nombre en la Rama Judicial y le avisamos si aparecen en un proceso nuevo, para que confirme si es su cliente.',
        'Sin avisos por WhatsApp ni vigilancia de clientes por cedula o NIT' => 'Sin avisos por WhatsApp ni vigilancia de clientes',
        'vigilancia de clientes por cedula o NIT.' => 'vigilancia de clientes.',
        'nombre del cliente, razon social, cedula o NIT' => 'nombre del cliente, razon social o radicado',
        'SAMAI, vigilancia por cedula, IA privada' => 'SAMAI, vigilancia de clientes, IA privada',
    ];

    public function up(): void
    {
        if (Schema::hasColumn('firms', 'plan_limits_notice_at')) {
            // Solo el aviso de 30 dias: el final (3 dias antes) todavia no se ha enviado a nadie.
            DB::table('firms')
                ->whereNotNull('plan_limits_notice_at')
                ->whereNull('plan_limits_final_notice_at')
                ->update(['plan_limits_notice_at' => null]);
        }

        if (! Schema::hasTable('mass_email_templates')) {
            return;
        }

        foreach (DB::table('mass_email_templates')->get(['id', 'subject', 'body']) as $template) {
            $subject = strtr((string) $template->subject, self::TEXT_FIXES);
            $body = strtr((string) $template->body, self::TEXT_FIXES);

            if ($subject !== $template->subject || $body !== $template->body) {
                DB::table('mass_email_templates')->where('id', $template->id)->update(['subject' => $subject, 'body' => $body]);
            }
        }
    }

    public function down(): void
    {
        // Correccion de datos: no se revierte.
    }
};
