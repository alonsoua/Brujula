<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAlumnosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('alumnos', function (Blueprint $table) {
            $table->id();
            $table->date('fechaInscripcion');
            $table->string('numMatricula')->nullable();
            $table->string('tipoDocumento')->comment('RUT o IPE');
            $table->string('rut')->unique();
            $table->string('nombres');
            $table->string('primerApellido');
            $table->string('segundoApellido');
            $table->string('correo')->nullable();
            $table->string('genero')->comment('Femenino o Masculino');
            $table->date('fechaNacimiento');
            $table->boolean('paci')->nullable();
            $table->boolean('pie')->nullable();
            $table->integer('numLista');
            $table->string('estado');
            $table->string('idDiagnostico')->nullable();
            $table->string('idPrioritario')->nullable();
            $table->string('idCurso');
            $table->string('idEstablecimiento');
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
        Schema::dropIfExists('alumnos');
    }
}
