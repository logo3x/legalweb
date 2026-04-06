<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('max_tyba_queries')->default(0)->after('max_storage_mb');
        });

        Schema::table('firms', function (Blueprint $table) {
            $table->unsignedInteger('tyba_queries_used')->default(0)->after('onboarding_completed');
            $table->dateTime('tyba_queries_reset_at')->nullable()->after('tyba_queries_used');
        });

        Schema::table('legal_cases', function (Blueprint $table) {
            $table->dateTime('last_tyba_sync')->nullable()->after('portal_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('max_tyba_queries');
        });

        Schema::table('firms', function (Blueprint $table) {
            $table->dropColumn(['tyba_queries_used', 'tyba_queries_reset_at']);
        });

        Schema::table('legal_cases', function (Blueprint $table) {
            $table->dropColumn('last_tyba_sync');
        });
    }
};
