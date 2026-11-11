<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Autorizacion del cliente para recibir avisos por WhatsApp (Ley 1581 de 2012).
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('whatsapp_enabled')->default(false)->after('phone');
            $table->timestamp('whatsapp_consent_at')->nullable()->after('whatsapp_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_enabled', 'whatsapp_consent_at']);
        });
    }
};
