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
        Schema::create('juzgados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ciudad')
                    ->nullable()
                    ->constrained('ciudades')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            $table->string('nombre');
            $table->string('email1');
            $table->string('email2')->nullable();
            $table->string('tel1');
            $table->string('tel2')->nullable();
            $table->string('juez');
            $table->string('secretario')->nullable();
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
        Schema::dropIfExists('juzgados');
    }
};
