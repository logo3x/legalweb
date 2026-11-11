<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fecha en que se aviso al cliente que debe aportar el documento (evita avisos duplicados).
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->timestamp('client_notified_at')->nullable()->after('received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('client_notified_at');
        });
    }
};
