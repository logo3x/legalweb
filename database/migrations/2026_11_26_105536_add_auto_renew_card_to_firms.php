<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cobro automatico de la renovacion con tarjeta (fuente de pago de Wompi). Nunca se guarda el
 * numero de la tarjeta: solo el id de la fuente de pago, la marca y los ultimos 4 digitos.
 * Las renovaciones automaticas se marcan en subscriptions.is_auto_renewal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            $table->string('wompi_payment_source_id', 40)->nullable();
            $table->string('card_brand', 30)->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_owner_email')->nullable();
            $table->timestamp('auto_renew_authorized_at')->nullable();
            $table->unsignedTinyInteger('auto_renew_failures')->default(0);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('is_auto_renewal')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            $table->dropColumn(['wompi_payment_source_id', 'card_brand', 'card_last_four', 'card_owner_email', 'auto_renew_authorized_at', 'auto_renew_failures']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('is_auto_renewal');
        });
    }
};
