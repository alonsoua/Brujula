<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDashLdConexionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dash_ld_conexion_logs', function (Blueprint $table) {
            $table->id();
            $table->string('state')->comment('1: Success, 2: Error');
            $table->string('message');
            $table->string('nombreCurso');
            $table->string('idLdConexion');
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
        Schema::dropIfExists('dash_ld_conexion_logs');
    }
}
