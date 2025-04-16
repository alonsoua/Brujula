<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubperiodosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->create('subperiodos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 20);
            $table->date('fecha_inicio');
            $table->date('fecha_termino');
            $table->integer('idAjuste')->comment('Tabla de master');
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
        Schema::connection('establecimiento')->dropIfExists('subperiodos');
    }
}
