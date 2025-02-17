<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEncuestaRespuestasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->create('encuesta_respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encuesta_opcion_id')->nullable()->constrained('encuesta_opciones')->onDelete('cascade');
            $table->text('texto_respuesta')->nullable();
            $table->foreignId('encuesta_pregunta_id')->constrained()->onDelete('cascade');
            $table->foreignId('encuesta_participante_id')->constrained()->onDelete('cascade');
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
        Schema::connection('establecimiento')->dropIfExists('encuesta_respuestas');
    }
}
