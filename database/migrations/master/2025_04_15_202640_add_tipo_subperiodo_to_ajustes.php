<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoSubperiodoToAjustes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('master')->table('ajustes', function (Blueprint $table) {
            $table->enum('tipo_subperiodo', ['sem', 'trim'])
                ->default('sem')
                ->after('evaluaciones_activo')
                ->comment('Define si el subperiodo es semestral o trimestral');
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
            $table->dropColumn('tipo_subperiodo');
        });
    }
}
