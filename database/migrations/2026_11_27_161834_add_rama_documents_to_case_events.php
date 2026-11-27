<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca las actuaciones que tienen documentos (autos, memoriales...) publicados en la Rama Judicial,
 * para ofrecer verlos, descargarlos y guardarlos en los documentos del caso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_events', function (Blueprint $table) {
            $table->boolean('rama_documents')->default(false)->after('rama_ref');
        });
    }

    public function down(): void
    {
        Schema::table('case_events', function (Blueprint $table) {
            $table->dropColumn('rama_documents');
        });
    }
};
