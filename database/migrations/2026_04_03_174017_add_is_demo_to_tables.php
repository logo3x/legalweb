<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('firm_id');
        });

        Schema::table('legal_cases', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('firm_id');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('is_demo');
        });

        Schema::table('legal_cases', function (Blueprint $table) {
            $table->dropColumn('is_demo');
        });
    }
};
