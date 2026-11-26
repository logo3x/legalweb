<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El plazo de un paso del flujo corre en dias habiles desde que el abogado lo inicia (started_at),
 * no desde la ultima edicion (updated_at). deadline_notices guarda los avisos ya enviados (3, 1, 0)
 * para no repetirlos y para enviar el que falte si un dia no corrio el cron.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_flow_progress', function (Blueprint $table) {
            $table->dateTime('started_at')->nullable()->after('status');
            $table->json('deadline_notices')->nullable()->after('started_at');
        });

        // Pasos ya iniciados: la mejor estimacion disponible de su inicio es la ultima actualizacion.
        DB::table('case_flow_progress')
            ->where('status', 'en_progreso')
            ->update(['started_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('case_flow_progress', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'deadline_notices']);
        });
    }
};
