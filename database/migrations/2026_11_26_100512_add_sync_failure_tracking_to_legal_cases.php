<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estado real de la consulta a la Rama Judicial / SAMAI por caso. last_tyba_sync queda solo
 * para consultas exitosas; un fallo se guarda aparte, se reintenta y se avisa al abogado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_cases', function (Blueprint $table) {
            $table->timestamp('sync_failed_at')->nullable()->after('last_tyba_sync')->index();
            $table->string('sync_error', 255)->nullable()->after('sync_failed_at');
            $table->unsignedSmallInteger('sync_failures')->default(0)->after('sync_error');
        });
    }

    public function down(): void
    {
        Schema::table('legal_cases', function (Blueprint $table) {
            $table->dropIndex(['sync_failed_at']);
            $table->dropColumn(['sync_failed_at', 'sync_error', 'sync_failures']);
        });
    }
};
