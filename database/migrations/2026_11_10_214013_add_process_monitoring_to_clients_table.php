<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vigilancia automatica de procesos por documento del cliente.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('process_monitoring_enabled')->default(false)->after('notes');
            $table->timestamp('process_monitoring_last_at')->nullable()->after('process_monitoring_enabled');
            $table->json('process_monitoring_known')->nullable()->after('process_monitoring_last_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['process_monitoring_enabled', 'process_monitoring_last_at', 'process_monitoring_known']);
        });
    }
};
