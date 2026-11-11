<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tyba_sync_logs', function (Blueprint $table) {
            $table->json('cambios')->nullable()->after('mensaje');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tyba_sync_logs', function (Blueprint $table) {
            $table->dropColumn('cambios');
        });
    }
};
