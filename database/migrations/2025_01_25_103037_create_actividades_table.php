<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActividadesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('master')->create('actividades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->longText('descripcion')->nullable();
            $table->unsignedBigInteger('idObjetivo');
            $table->foreign('idObjetivo')
                ->references('id')
                ->on('objetivos')
                ->onUpdate('restrict')
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
        Schema::connection('master')->dropIfExists('actividades');
    }
}
