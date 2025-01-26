<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstablecimientosUsuariosRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('master')->create('estab_usuarios_roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('idEstablecimiento');
            $table->foreign('idEstablecimiento')
                ->references('id')
                ->on('establecimientos')

                ->onDelete('restrict');
            $table->unsignedBigInteger('idUsuario');
            $table->foreign('idUsuario')
                ->references('id')
                ->on('usuarios')

                ->onDelete('restrict');
            $table->unsignedBigInteger('idRol');
            $table->foreign('idRol')
                ->references('id')
                ->on('roles')

                ->onDelete('restrict');
            $table->boolean('estado')->default(true)->comment('true: activo - false: inactivo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('master')->dropIfExists('estab_usuarios_roles');
    }
}
