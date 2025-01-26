<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->create('notas', function (Blueprint $table) {
            $table->id();
            $table->decimal('nota', 2, 1);
            $table->unsignedBigInteger('idAlumno');
            $table->foreign('idAlumno')
                ->references('id')
                ->on('alumnos')
                ->onDelete('no action');
            $table->integer('idPeriodo');
            $table->unsignedBigInteger('idCurso');
            $table->foreign('idCurso')
                ->references('id')
                ->on('cursos')
                ->onDelete('no action');
            $table->integer('idAsignatura');
            $table->integer('idObjetivo');
            $table->string('tipoObjetivo')->nullable();
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
        Schema::connection('establecimiento')->dropIfExists('notas');
    }
}
