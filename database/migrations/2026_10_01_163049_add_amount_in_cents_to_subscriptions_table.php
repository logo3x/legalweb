<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Monto que se envio a Wompi al crear el checkout, para validar el pago recibido.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'amount_in_cents')) {
                $table->unsignedBigInteger('amount_in_cents')->nullable()->after('billing_cycle');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'amount_in_cents')) {
                $table->dropColumn('amount_in_cents');
            }
        });
    }
};
