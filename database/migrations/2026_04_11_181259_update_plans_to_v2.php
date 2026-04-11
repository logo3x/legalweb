<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar plan Gratuito: sin limite de casos, trial 3 meses
        DB::table('plans')->where('slug', 'gratuito')->update([
            'max_cases' => 0,
            'has_notifications' => true,
            'description' => '3 meses de prueba con todas las funcionalidades',
        ]);

        // Renombrar Profesional -> Pro
        DB::table('plans')->where('slug', 'profesional')->update([
            'name' => 'Pro',
            'slug' => 'pro',
            'has_notifications' => true,
            'has_portal' => true,
        ]);

        // Actualizar Firma
        DB::table('plans')->where('slug', 'firma')->update([
            'has_notifications' => true,
            'has_portal' => true,
        ]);

        // Actualizar trial de subscripciones existentes a 3 meses desde su inicio
        DB::table('subscriptions')
            ->whereNotNull('trial_ends_at')
            ->whereRaw('trial_ends_at > NOW()')
            ->update([
                'trial_ends_at' => DB::raw('DATE_ADD(starts_at, INTERVAL 3 MONTH)'),
            ]);
    }

    public function down(): void
    {
        DB::table('plans')->where('slug', 'gratuito')->update([
            'max_cases' => 3,
            'has_notifications' => false,
        ]);

        DB::table('plans')->where('slug', 'pro')->update([
            'name' => 'Profesional',
            'slug' => 'profesional',
        ]);
    }
};
