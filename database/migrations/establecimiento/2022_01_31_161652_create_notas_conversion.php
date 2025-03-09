<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotasConversion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->create('notas_conversion', function (Blueprint $table) {
            $table->id();
            $table->integer('cantidadIndicadores');
            $table->integer('puntajeObtenido');
            $table->decimal('nota', 2, 1);
            $table->string('idPeriodo')->nullable();
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
        Schema::connection('establecimiento')->dropIfExists('notas_conversion');
    }
}
