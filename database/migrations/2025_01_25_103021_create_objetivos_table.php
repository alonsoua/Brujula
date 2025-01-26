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
        Schema::connection('master')->create('objetivos', function (Blueprint $table) {
            $table->id();
            $table->longText('nombre');
            $table->string('abreviatura')->nullable();
            $table->integer('priorizacion')->nullable();
            $table->integer('priorizacionInterna')->nullable();
            $table->unsignedBigInteger('idEje')->nullable();
            $table->foreign('idEje')
                ->references('id')
                ->on('ejes')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table->unsignedBigInteger('idUnidad')->nullable();
            $table->foreign('idUnidad')
                ->references('id')
                ->on('unidades')
                ->onUpdate('restrict')
                ->onDelete('restrict');
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
        Schema::connection('master')->dropIfExists('objetivos');
    }
}
