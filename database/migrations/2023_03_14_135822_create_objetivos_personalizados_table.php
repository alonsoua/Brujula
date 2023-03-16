<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjetivosPersonalizadosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('objetivos_personalizados', function (Blueprint $table) {
            $table->id();
            $table->longText('nombre');
            $table->string('abreviatura')->nullable();
            $table->integer('priorizacion')->nullable();
            $table->integer('idEje')->nullable();
            $table->integer('idUnidad')->nullable();
            $table->integer('idEstablecimiento');
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
        Schema::dropIfExists('objetivos_personalizados');
    }
}
