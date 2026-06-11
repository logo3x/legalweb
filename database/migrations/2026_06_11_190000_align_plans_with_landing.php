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

        // Alinear precios con la landing: 1 solo plan pago a 120.000 COP/mes
        // y semestral 600.000 (con el 17% de descuento implicito = 100k/mes).
        // Idempotente: solo actualiza si encuentra los planes existentes.

        $paidPrice = 120000;
        $biannualPrice = 600000; // ~100k/mes con descuento 17%

        // El plan "profesional" si existe queda como el unico pago
        $updated = DB::table('plans')
            ->whereIn('slug', ['profesional', 'pro'])
            ->update([
                'price_monthly' => $paidPrice,
                'price_yearly' => $biannualPrice,
                'updated_at' => now(),
            ]);

        // Si no existe la fila "profesional" pero existen otros pagos, alinearlos tambien
        if ($updated === 0) {
            DB::table('plans')
                ->where('price_monthly', '>', 0)
                ->update([
                    'price_monthly' => $paidPrice,
                    'price_yearly' => $biannualPrice,
                    'updated_at' => now(),
                ]);
        }

        // El plan gratuito queda confirmado en 0 (por si alguien lo cambio)
        DB::table('plans')
            ->where('slug', 'gratuito')
            ->update([
                'price_monthly' => 0,
                'price_yearly' => 0,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No revertimos precios — es un ajuste de catalogo, no estructural.
    }
};
