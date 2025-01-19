<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeIdPeriodoDatatypeToPuntajesIndicadores extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('puntajes_indicadores', function (Blueprint $table) {
            DB::statement("ALTER TABLE puntajes_indicadores MODIFY idPeriodo INT(11) NULL");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('puntajes_indicadores', function (Blueprint $table) {
            DB::statement("ALTER TABLE puntajes_indicadores MODIFY idPeriodo INT(11) NOT NULL");
        });
    }
}
