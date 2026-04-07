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
        Schema::create('tyba_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_case_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['ok', 'error', 'sin_cambios'])->default('ok');
            $table->unsignedInteger('nuevas_actuaciones')->default(0);
            $table->string('mensaje')->nullable();
            $table->enum('origen', ['manual', 'automatico'])->default('automatico');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tyba_sync_logs');
    }
};
