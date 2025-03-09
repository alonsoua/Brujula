<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAlumnosCursosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->create('alumnos_cursos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idAlumno');
            $table->foreign('idAlumno')
                ->references('id')
                ->on('alumnos')
                ->onDelete('no action');
            $table->unsignedBigInteger('idCurso');
            $table->foreign('idCurso')
                ->references('id')
                ->on('cursos')
                ->onDelete('no action');
            $table->string('estado');
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
        Schema::connection('establecimiento')->dropIfExists('alumnos_cursos');
    }
}
