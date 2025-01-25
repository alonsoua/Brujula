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
            $table->unsignedBigInteger('id_establecimiento');
            $table->foreign('id_establecimiento')
                ->references('id')
                ->on('establecimientos')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table->unsignedBigInteger('id_usuario');
            $table->foreign('id_usuario')
                ->references('id')
                ->on('usuarios')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table->unsignedBigInteger('id_rol');
            $table->foreign('id_rol')
                ->references('id')
                ->on('roles')
                ->onUpdate('restrict')
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
