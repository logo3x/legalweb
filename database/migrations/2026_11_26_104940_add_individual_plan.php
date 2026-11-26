<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Plan Individual (aprobado el 26 de noviembre de 2026): $59.900/mes o $299.500 por 6 meses,
 * 1 usuario, hasta 80 procesos activos, 30 consultas de IA al mes y revision diaria. Para el
 * litigante independiente que no necesita equipo. La vigilancia de clientes queda en
 * Profesional y Firma.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Solo donde existen los planes base (no en una base vacia de pruebas).
        if (! DB::table('plans')->whereIn('slug', ['gratuito', 'pro', 'profesional'])->exists()) {
            return;
        }

        $plan = [
            'name' => 'Individual',
            'description' => 'Para el abogado independiente: 1 usuario y hasta 80 procesos activos.',
            'price_monthly' => 59900,
            'price_yearly' => 299500,
            'currency' => 'COP',
            'max_cases' => 80,
            'max_users' => 1,
            'max_storage_mb' => 1024,
            'ai_monthly_limit' => 30,
            'has_portal' => true,
            'has_notifications' => true,
            'has_whatsapp' => false,
            'has_client_monitoring' => false,
            'sync_every_days' => 1,
            'is_active' => true,
            'sort_order' => 2,
            'updated_at' => now(),
        ];

        if (DB::table('plans')->where('slug', 'individual')->exists()) {
            DB::table('plans')->where('slug', 'individual')->update($plan);
        } else {
            DB::table('plans')->insert($plan + ['slug' => 'individual', 'created_at' => now()]);
        }

        DB::table('plans')->whereIn('slug', ['pro', 'profesional'])->update(['sort_order' => 3, 'updated_at' => now()]);
        DB::table('plans')->where('slug', 'firma')->update(['sort_order' => 4, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('plans')->where('slug', 'individual')->update(['is_active' => false, 'updated_at' => now()]);
    }
};
