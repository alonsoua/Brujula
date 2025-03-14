<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndicadoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('master')->create('indicadores', function (Blueprint $table) {
            $table->id();
            $table->longText('nombre');
            $table->unsignedBigInteger('idObjetivo');
            $table->foreign('idObjetivo')
                ->references('id')
                ->on('objetivos')

                ->onDelete('cascade');
            $table->string('estado')->nullable();
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
        Schema::connection('master')->dropIfExists('indicadores');
    }
}
