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
        Schema::create('tramites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tipoproceso')
                    ->nullable()
                    ->constrained('tipoprocesos')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            $table->string('nombre')->nullable();
            $table->string('desc_esquema')->nullable();
            $table->string('desc_tramite')->nullable();
            $table->string('anexo_esquema')->nullable();
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
        Schema::dropIfExists('tramites');
    }
};
