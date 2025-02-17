<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEncuestaOpcionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->create('encuesta_opciones', function (Blueprint $table) {
            $table->id();
            $table->char('opcion', 1);
            $table->string('texto', 250);
            $table->string('imagen')->nullable();
            $table->foreignId('encuesta_pregunta_id')->constrained()->onDelete('cascade');
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo')
                ->comment('Activo, Inactivo');
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
        Schema::connection('establecimiento')->dropIfExists('encuesta_opciones');
    }
}
