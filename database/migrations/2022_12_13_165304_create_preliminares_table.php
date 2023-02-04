<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('preliminares', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('id_cliente')
                    ->nullable()
                    ->constrained('clientes')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            $table->string('relato',10000);
            $table->string('gestion')->nullable();
            $table->string('des_gestion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('preliminares');
    }
};
