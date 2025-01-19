<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoObjetivoToIndicadorPersonalizados extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('indicador_personalizados', function (Blueprint $table) {
            $table->string('tipo_objetivo')
                  ->after('idObjetivo')
                  ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('indicador_personalizados', function (Blueprint $table) {
            $table->dropColumn('tipo_objetivo');
        });
    }
}
