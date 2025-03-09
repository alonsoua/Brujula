<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoToIndicadoresPersonalizadosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::connection('establecimiento')->table('indicadores_personalizados', function (Blueprint $table) {
            $table->string('tipo')->after('nombre')->comment('Interno - Personal');
            $table->dropColumn('tipo_indicador');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('establecimiento')->table('indicadores_personalizados', function (Blueprint $table) {
            $table->string('tipo_indicador')->default('Generico')->comment('Valores: generico, personal');
            $table->dropColumn('tipo');
        });
    }
}
