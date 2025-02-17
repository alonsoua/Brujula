<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEncuestaParticipantesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->create('encuesta_participantes', function (Blueprint $table) {
            $table->id();
            $table->string('rut', 15);
            $table->string('nombre', 90);
            $table->string('primerApellido', 90);
            $table->string('segundoApellido', 90);
            $table->foreignId('rol_id');
            $table->foreignId('curso_id');
            $table->foreignId('usuario_id')->nullable();
            $table->foreignId('encuesta_id')->constrained()->onDelete('cascade');
            $table->enum('estado', ['En Proceso', 'Finalizado'])->default('En Proceso')
                ->comment('En Proceso, Finalizado');
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
        Schema::connection('establecimiento')->dropIfExists('encuesta_participantes');
    }
}
