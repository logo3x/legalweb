<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fecha en que el usuario pidio no recibir campanas masivas (enlace de baja).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'mass_email_opt_out_at')) {
                $table->timestamp('mass_email_opt_out_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'mass_email_opt_out_at')) {
                $table->dropColumn('mass_email_opt_out_at');
            }
        });
    }
};
