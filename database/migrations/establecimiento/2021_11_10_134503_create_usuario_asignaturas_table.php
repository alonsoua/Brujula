<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuarioAsignaturasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->create('usuario_asignaturas', function (Blueprint $table) {
            $table->id();
            $table->integer('idUsuarioEstablecimiento');
            $table->unsignedBigInteger('idCurso');
            $table->foreign('idCurso')
                ->references('id')
                ->on('cursos')
                ->onDelete('cascade');
            $table->integer('idAsignatura');
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
        Schema::connection('establecimiento')->dropIfExists('usuario_asignaturas');
    }
}
