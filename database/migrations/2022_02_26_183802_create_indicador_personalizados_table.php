<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndicadorPersonalizadosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('indicador_personalizados', function (Blueprint $table) {
            $table->id();
            $table->longText('nombre');
            $table->integer('idObjetivo');
            $table->integer('idPeriodo');
            $table->string('estado')->nullable();
            $table->integer('idUsuario_created');
            $table->integer('idUsuario_updated')->nullable();
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
        Schema::dropIfExists('indicador_personalizados');
    }
}
