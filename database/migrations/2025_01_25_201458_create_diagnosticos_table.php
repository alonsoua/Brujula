<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiagnosticosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('master')->create('diagnosticos_pie', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipoNee')->comment('Permanente o Transitorio');
            $table->string('abreviatura')->nullable();
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
        Schema::connection('master')->dropIfExists('diagnosticos_pie');
    }
}
