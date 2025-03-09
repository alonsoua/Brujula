<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndicadoresPersonalizadosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->create('indicadores_personalizados', function (Blueprint $table) {
            $table->id();
            $table->longText('nombre');
            $table->integer('idObjetivo');
            $table->string('tipo_indicador')->default('Generico')->comment('Valores: generico, personal');
            $table->string('tipo_objetivo')->nullable()->comment('Ministerio - Interno');
            $table->unsignedBigInteger('idCurso');
            $table->foreign('idCurso')
                ->references('id')
                ->on('cursos')
                ->onDelete('no action');
            $table->integer('idPeriodo')->nullable();
            $table->string('estado')->default('Activo')->comment('Creado (espera aprobación) - Activo (aprobado) - Inactivo (eliminado)');
            $table->integer('idUsuario_created');
            $table->integer('idUsuario_updated')->nullable();
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
        Schema::connection('establecimiento')->dropIfExists('indicadores_personalizados');
    }
}
