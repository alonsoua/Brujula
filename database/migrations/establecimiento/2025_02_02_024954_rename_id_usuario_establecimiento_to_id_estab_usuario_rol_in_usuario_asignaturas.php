<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameIdUsuarioEstablecimientoToIdEstabUsuarioRolInUsuarioAsignaturas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->table('usuario_asignaturas', function (Blueprint $table) {
            $table->dropColumn('idUsuarioEstablecimiento');
            $table->integer('idEstabUsuarioRol')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('establecimiento')->table('usuario_asignaturas', function (Blueprint $table) {
            $table->integer('idUsuarioEstablecimiento')->after('id');
            $table->dropColumn('idEstabUsuarioRol');
        });
    }
}
