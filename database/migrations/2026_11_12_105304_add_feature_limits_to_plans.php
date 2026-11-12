<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Limites por plan (aprobados el 12 de noviembre de 2026):
 *
 * - Prueba (3 meses): como Profesional.
 * - Gratuito (al terminar la prueba): 5 casos activos, 1 usuario, 10 consultas de IA al mes,
 *   sin WhatsApp ni vigilancia de clientes, sincronizacion semanal.
 * - Profesional ($120.000/mes): casos ilimitados, 3 usuarios, todo incluido.
 * - Firma ($250.000/mes): casos ilimitados, 10 usuarios, todo incluido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // null = sin limite.
            $table->unsignedInteger('ai_monthly_limit')->nullable()->after('max_storage_mb');
            $table->boolean('has_whatsapp')->default(true)->after('has_notifications');
            $table->boolean('has_client_monitoring')->default(true)->after('has_whatsapp');
            $table->unsignedSmallInteger('sync_every_days')->default(1)->after('has_client_monitoring');
        });

        Schema::table('firms', function (Blueprint $table) {
            $table->timestamp('plan_limits_notice_at')->nullable();
            $table->timestamp('plan_limits_final_notice_at')->nullable();
        });

        DB::table('plans')->where('slug', 'gratuito')->update([
            'max_cases' => 5,
            'max_users' => 1,
            'ai_monthly_limit' => 10,
            'has_whatsapp' => false,
            'has_client_monitoring' => false,
            'sync_every_days' => 7,
            'sort_order' => 1,
            'updated_at' => now(),
        ]);

        DB::table('plans')->whereIn('slug', ['pro', 'profesional'])->update([
            'max_cases' => 0,
            'max_users' => 3,
            'ai_monthly_limit' => null,
            'sort_order' => 2,
            'updated_at' => now(),
        ]);

        $firmPlan = [
            'name' => 'Firma',
            'description' => 'Para firmas con equipo: hasta 10 usuarios.',
            'price_monthly' => 250000,
            'price_yearly' => 1250000,
            'max_cases' => 0,
            'max_users' => 10,
            'ai_monthly_limit' => null,
            'has_portal' => true,
            'has_notifications' => true,
            'is_active' => true,
            'sort_order' => 3,
            'updated_at' => now(),
        ];

        if (DB::table('plans')->where('slug', 'firma')->exists()) {
            DB::table('plans')->where('slug', 'firma')->update($firmPlan);
        } elseif (DB::table('plans')->whereIn('slug', ['gratuito', 'pro', 'profesional'])->exists()) {
            // Solo en bases ya configuradas: en una instalacion nueva los planes los crea el seeder.
            DB::table('plans')->insert($firmPlan + ['slug' => 'firma', 'currency' => 'COP', 'max_storage_mb' => 5120, 'created_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            $table->dropColumn(['plan_limits_notice_at', 'plan_limits_final_notice_at']);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['ai_monthly_limit', 'has_whatsapp', 'has_client_monitoring', 'sync_every_days']);
        });

        DB::table('plans')->where('slug', 'firma')->update(['is_active' => false]);
    }
};
