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
        Schema::create('legal_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number', 50)->unique();
            $table->string('external_case_number', 50)->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('case_type_id')->constrained();
            $table->foreignId('client_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('status', 30)->default('abierto');
            $table->string('court')->nullable();
            $table->string('judge')->nullable();
            $table->string('opposing_party')->nullable();
            $table->string('priority', 10)->default('media');
            $table->date('started_at')->nullable();
            $table->date('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_cases');
    }
};
