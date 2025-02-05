<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsLoginToEstabUsuarioRol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('master')->table('estab_usuarios_roles', function (Blueprint $table) {
            $table->unsignedInteger('conexiones')->after('idRol')->default(0)->nullable(false);
            $table->datetime('ultima_conexion')->after('conexiones')->nullable();
            $table->boolean('isLogin')->after('ultima_conexion')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('master')->table('estab_usuarios_roles', function (Blueprint $table) {
            $table->dropColumn('conexiones');
            $table->dropColumn('ultima_conexion');
            $table->dropColumn('isLogin');
        });
    }
}
