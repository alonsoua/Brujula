<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdPeriodoToNotasConversion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notas_conversion', function (Blueprint $table) {
            $table->string('idPeriodo')
                  ->after('nota')
                  ->nullable();
            $table->string('idEstablecimiento')
                  ->after('idPeriodo')
                  ->nullable();
            $table->string('estado')
                    ->after('idEstablecimiento')
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
        Schema::table('notas_conversion', function (Blueprint $table) {
            $table->dropColumn('idPeriodo');
            $table->dropColumn('idEstablecimiento');
            $table->dropColumn('estado');
        });
    }
}
