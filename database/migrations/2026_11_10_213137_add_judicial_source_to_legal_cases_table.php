<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fuente del proceso judicial: null o "rama" = Rama Judicial (CPNU), "samai" = SAMAI.
     */
    public function up(): void
    {
        Schema::table('legal_cases', function (Blueprint $table) {
            $table->string('judicial_source', 20)->nullable()->after('external_case_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('legal_cases', function (Blueprint $table) {
            $table->dropColumn('judicial_source');
        });
    }
};
