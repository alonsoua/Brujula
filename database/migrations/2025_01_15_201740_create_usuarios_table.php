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
            $table->string('nombre')->nullable();
            $table->datetime('ultima_conexion')->nullable();
            $table->unsignedInteger('conexiones')->default(0)->nullable(false);
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
