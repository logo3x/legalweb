<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Descuento aplicado en el checkout. El canje del codigo se registra solo cuando el pago se aprueba.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'discount_code_id')) {
                $table->foreignId('discount_code_id')->nullable()->after('amount_in_cents')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('subscriptions', 'original_amount')) {
                $table->unsignedInteger('original_amount')->nullable()->after('discount_code_id');
            }
            if (! Schema::hasColumn('subscriptions', 'discount_amount')) {
                $table->unsignedInteger('discount_amount')->nullable()->after('original_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'discount_code_id')) {
                $table->dropConstrainedForeignId('discount_code_id');
            }
            foreach (['original_amount', 'discount_amount'] as $column) {
                if (Schema::hasColumn('subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
