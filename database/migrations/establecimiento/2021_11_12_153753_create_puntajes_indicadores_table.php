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
        Schema::connection('establecimiento')->create('puntajes_indicadores', function (Blueprint $table) {
            $table->id();
            $table->integer('idPeriodo')->nullable();
            $table->unsignedBigInteger('idCurso');
            $table->foreign('idCurso')
                ->references('id')
                ->on('cursos')
                ->onDelete('no action');
            $table->integer('idAsignatura');
            $table->integer('idIndicador');
            $table->unsignedBigInteger('idAlumno');
            $table->foreign('idAlumno')
                ->references('id')
                ->on('alumnos')
                ->onDelete('no action');
            $table->integer('puntaje')->nullable();
            $table->string('tipoIndicador');
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
        Schema::connection('establecimiento')->dropIfExists('puntajes_indicadores');
    }
}
