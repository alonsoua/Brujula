<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjetivosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->create('objetivos', function (Blueprint $table) {
            $table->id();
            $table->longText('nombre');
            $table->string('abreviatura')->nullable();
            $table->integer('priorizacion')->nullable();
            $table->integer('priorizacionInterna')->nullable();
            $table->unsignedBigInteger('idEje')->nullable();
            $table->foreign('idEje')
                ->references('id')
                ->on('ejes')
                ->onDelete('restrict');
            $table->integer('idUnidad')->nullable();
            $table->string('estado')->nullable();
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
        Schema::connection('establecimiento')->dropIfExists('objetivos');
    }
}
