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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ciudad')
                    ->nullable()
                    ->constrained('ciudades')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->string('direccion')->nullable();
            $table->string('email');
            $table->string('celular1');
            $table->string('celular2')->nullable();
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
        Schema::dropIfExists('clientes');
    }
};
