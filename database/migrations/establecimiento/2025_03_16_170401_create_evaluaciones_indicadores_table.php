<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluacionesIndicadoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->create('evaluaciones_indicadores', function (Blueprint $table) {
            $table->id();
            $table->integer('idObjetivo');
            $table->integer('idIndicador');
            $table->unsignedBigInteger('idEvaluacion');
            $table->foreign('idEvaluacion')->references('id')->on('evaluaciones')->onDelete('cascade');
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
        Schema::connection('establecimiento')->dropIfExists('evaluaciones_indicadores');
    }
}
