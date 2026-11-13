<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de los correos automaticos de ciclo de vida (bienvenida, primer proceso,
 * inactividad) para no repetirlos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lifecycle_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key', 40);
            $table->timestamp('sent_at');
            $table->unique(['user_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lifecycle_emails');
    }
};
