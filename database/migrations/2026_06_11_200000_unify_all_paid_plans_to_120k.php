<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plans')) {
            return;
        }

        // La landing vende un solo precio: 120.000 COP/mes (y 600.000 semestral).
        // Pero la tabla plans tiene 3 niveles legacy (gratuito + pro + firma).
        // Esta migracion:
        //   1. Confirma el plan "gratuito" en 0
        //   2. Renombra "pro" a "Profesional" y lo pone en 120.000 / 600.000
        //   3. Desactiva "firma" para que no aparezca como otra opcion
        // Idempotente: solo actualiza filas que existen.

        DB::table('plans')
            ->where('slug', 'gratuito')
            ->update([
                'price_monthly' => 0,
                'price_yearly' => 0,
                'is_active' => true,
                'updated_at' => now(),
            ]);

        DB::table('plans')
            ->whereIn('slug', ['pro', 'profesional'])
            ->update([
                'name' => 'Profesional',
                'price_monthly' => 120000,
                'price_yearly' => 600000,
                'is_active' => true,
                'updated_at' => now(),
            ]);

        DB::table('plans')
            ->where('slug', 'firma')
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        // Cualquier otro plan pago activo que haya quedado: alinear precio
        // para que el componente que toma el mas barato no muestre raros.
        DB::table('plans')
            ->where('price_monthly', '>', 0)
            ->where('price_monthly', '!=', 120000)
            ->where('is_active', true)
            ->update([
                'price_monthly' => 120000,
                'price_yearly' => 600000,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No revertimos — es ajuste de catalogo comercial.
    }
};
