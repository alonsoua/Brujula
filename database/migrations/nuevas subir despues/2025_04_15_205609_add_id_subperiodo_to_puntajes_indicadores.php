<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdSubperiodoToPuntajesIndicadores extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->table('puntajes_indicadores', function (Blueprint $table) {
            $table->unsignedBigInteger('idSubperiodo')
                ->default(1)
                ->after('idAsignatura');
            $table->foreign('idSubperiodo')
                ->references('id')
                ->on('subperiodos')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('establecimiento')->table('puntajes_indicadores', function (Blueprint $table) {
            $table->dropForeign(['idSubperiodo']);
            $table->dropColumn('idSubperiodo');
        });
    }
}
