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
        Schema::connection('establecimiento')->create('establecimientos', function (Blueprint $table) {
            $table->id();
            $table->string('rbd')->unique();
            $table->string('nombre');
            $table->string('insignia')->nullable();
            $table->string('correo');
            $table->string('telefono');
            $table->string('direccion');
            $table->string('dependencia');
            $table->integer('idPeriodoActivo')->nullable();
            $table->string('estado');
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
        Schema::connection('establecimiento')->dropIfExists('establecimientos');
    }
}
