<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEncuestaPreguntasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->create('encuesta_preguntas', function (Blueprint $table) {
            $table->id();
            $table->integer('numero')->default(1);
            $table->text('titulo', 250);
            $table->enum('tipo_pregunta', ['texto', 'opcion_multiple', 'checkbox'])
                ->comment('texto, opcion_multiple, checkbox');
            $table->string('imagen')->nullable();
            $table->foreignId('encuesta_id')->constrained()->onDelete('cascade');
            $table->foreignId('subcategoria_id')->nullable()->constrained()->onDelete('set null');
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
        Schema::connection('establecimiento')->dropIfExists('encuesta_preguntas');
    }
}
