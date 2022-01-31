<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAbreviaturaToDiagnosticosPie extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('diagnosticos_pie', function (Blueprint $table) {
            $table->string('abreviatura')
                  ->after('tipoNee')
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
        Schema::table('diagnosticos_pie', function (Blueprint $table) {
            $table->dropColumn('abreviatura');
        });
    }
}
