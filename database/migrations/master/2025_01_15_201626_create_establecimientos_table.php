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
        Schema::connection('master')->create('estab', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('bd_name')->nullable();
            $table->string('bd_pass')->nullable();
            $table->string('bd_user', 50)->nullable();
            $table->string('nombre');
            $table->string('rbd')->unique();
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
        Schema::connection('master')->dropIfExists('estab');
    }
}
