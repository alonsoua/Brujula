<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluacionesNotasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->create('evaluaciones_notas', function (Blueprint $table) {
            $table->id();
            $table->decimal('nota', 4, 2);
            $table->string('tipoIndicador');
            $table->unsignedBigInteger('idAlumno');
            $table->foreign('idAlumno')->references('id')->on('alumnos')->onDelete('no action');
            $table->unsignedBigInteger('idEvaluacion');
            $table->foreign('idEvaluacion')->references('id')->on('evaluaciones')->onDelete('cascade');
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
        Schema::connection('establecimiento')->dropIfExists('evaluaciones_notas');
    }
}
