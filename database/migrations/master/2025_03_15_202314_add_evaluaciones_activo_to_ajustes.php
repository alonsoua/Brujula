<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEvaluacionesActivoToAjustes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('master')->table('ajustes', function (Blueprint $table) {
            $table->boolean('evaluaciones_activo')
                ->default(false)
                ->after('ld_activo')
                ->comment('Define si el modulo de evaluaciones está activo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('master')->table('ajustes', function (Blueprint $table) {
            $table->dropColumn('evaluaciones_activo');
        });
    }
}
