<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEstadoToTipoEnseñanza extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tipo_enseñanza', function (Blueprint $table) {
            $table->string('estado')
                  ->after('codigo')
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
        Schema::table('tipo_enseñanza', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
}
