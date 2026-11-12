<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El documento del cliente era unico en toda la plataforma: una firma no podia registrar
 * a un cliente que ya existia en otra y, de paso, se enteraba de que esa cedula estaba registrada.
 * Ahora es unico dentro de cada firma.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['document_number']);
            $table->unique(['firm_id', 'document_number']);
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['firm_id', 'document_number']);
            $table->unique(['document_number']);
        });
    }
};
