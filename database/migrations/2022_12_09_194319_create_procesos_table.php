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
        Schema::create('procesos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_preliminar')
                    ->nullable()
                    ->constrained('preliminares')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

            $table->foreignId('id_tipoprocesos')
                    ->nullable()
                    ->constrained('tipoprocesos')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            $table->foreignId('id_claseproceso')
                    ->nullable()
                    ->constrained('claseprocesos')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();   

            $table->foreignId('id_naturaleza')
                    ->nullable()
                    ->constrained('naturalezas')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

            $table->foreignId('id_juzgado')
                    ->nullable()
                    ->constrained('juzgados')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();   
                    
            $table->foreignId('id_cliente')
                    ->nullable()
                    ->constrained('clientes')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
                    
            $table->foreignId('id_ciudad')
                    ->nullable()
                    ->constrained('ciudades')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            $table->string('nproceso');   
            $table->string('nombre');     
            $table->date('fecha_presentacion')->nullable();
            $table->date('fecha_radicacion')->nullable();
            $table->string('descripcion',10000)->nullable();
            $table->string('demandante');
            $table->string('contacto_demandante')->nullable();
            $table->string('demandado');
            $table->string('contacto_demandado')->nullable();



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
        Schema::dropIfExists('procesos');
    }
};
