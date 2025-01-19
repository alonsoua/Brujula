<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePuntajesIndicadoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('puntajes_indicadores', function (Blueprint $table) {
            $table->id();
            $table->integer('idPeriodo');
            $table->integer('idCurso');
            $table->integer('idAsignatura');
            $table->integer('idIndicador');
            $table->integer('idAlumno');
            $table->integer('puntaje')->nullable();
            $table->string('estado');
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
        Schema::dropIfExists('puntajes_indicadores');
    }
}
