<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstablecimientosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('master')->create('establecimientos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('bd_name')->nullable();
            $table->string('bd_user', 50)->nullable();
            $table->string('bd_pass')->nullable();
            $table->string('bd_host', 255)->default('127.0.0.1')->after('bd_user');
            $table->string('bd_port', 10)->default('3306')->after('bd_host');
            $table->string('nombre');
            $table->string('rbd')->unique();
            $table->string('insignia')->nullable();
            $table->string('correo');
            $table->string('telefono');
            $table->string('direccion');
            $table->string('dependencia');
            $table->integer('idPeriodoActivo')->nullable();
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
        Schema::connection('master')->dropIfExists('establecimientos');
    }
}
