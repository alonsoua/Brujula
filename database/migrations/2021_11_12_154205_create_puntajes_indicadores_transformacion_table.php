<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePuntajesIndicadoresTransformacionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('puntajes_indicadores_transformacion', function (Blueprint $table) {
            $table->id();
            $table->integer('puntaje');
            $table->integer('rangoDesde');
            $table->integer('rangoHasta');
            $table->string('nivelLogro');
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
        Schema::dropIfExists('puntajes_indicadores_transformacion');
    }
}
