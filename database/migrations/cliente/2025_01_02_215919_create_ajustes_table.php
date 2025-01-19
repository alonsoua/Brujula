<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAjustesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ajustes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idEstablecimiento');
            $table->unsignedBigInteger('idPeriodo');
            $table->enum('tipo_nota', ['concepto', 'numero']);
            // Foreign keys (opcional, dependiendo de tu modelo)
            $table->foreign('idEstablecimiento')->references('id')->on('establecimientos')->onDelete('cascade');
            $table->foreign('idPeriodo')->references('id')->on('periodos')->onDelete('cascade');
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
        Schema::dropIfExists('ajustes');
    }
}
