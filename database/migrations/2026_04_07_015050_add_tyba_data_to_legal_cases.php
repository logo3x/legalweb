<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_cases', function (Blueprint $table) {
            $table->json('tyba_data')->nullable()->after('last_tyba_sync');
        });
    }

    public function down(): void
    {
        Schema::table('legal_cases', function (Blueprint $table) {
            $table->dropColumn('tyba_data');
        });
    }
};
