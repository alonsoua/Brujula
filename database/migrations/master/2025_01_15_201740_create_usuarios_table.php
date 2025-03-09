<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuariosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('master')->create('usuarios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('correo')->unique();
            $table->text('password');
            $table->string('avatar')->nullable();
            $table->string('rut');
            $table->string('nombres');
            $table->string('primerApellido');
            $table->string('segundoApellido');
            $table->datetime('ultima_conexion')->nullable();
            $table->unsignedInteger('conexiones')->default(0)->nullable(false);
            $table->string('estado');
            $table->integer('idUsuarioCreated')->nullable();
            $table->integer('idUsuarioUpdated')->nullable();
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
        Schema::connection('master')->dropIfExists('usuarios');
    }
}
