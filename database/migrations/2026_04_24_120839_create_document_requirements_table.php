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
        Schema::create('document_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_case_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('responsible', ['cliente', 'abogado', 'firma', 'contraparte', 'juzgado', 'otro'])->default('cliente');
            $table->string('entity')->nullable();
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->enum('status', ['pendiente', 'solicitado', 'en_tramite', 'recibido', 'no_aplica'])->default('pendiente');
            $table->enum('priority', ['baja', 'media', 'alta', 'urgente'])->default('media');
            $table->date('due_date')->nullable();
            $table->date('received_at')->nullable();
            $table->string('external_url')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['legal_case_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_requirements');
    }
};
