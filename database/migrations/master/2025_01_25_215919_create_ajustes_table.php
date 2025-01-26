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
        Schema::connection('master')->create('ajustes', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_nota', ['concepto', 'numero']);
            $table->boolean('ld_activo')->default(false);
            $table->unsignedBigInteger('idEstablecimiento');
            $table->foreign('idEstablecimiento')
                ->references('id')
                ->on('establecimientos')

                ->onDelete('cascade');
            $table->unsignedBigInteger('idPeriodo');
            $table->foreign('idPeriodo')
                ->references('id')
                ->on('periodos')

                ->onDelete('cascade');
            $table->dateTime('fecha_inicio_periodo')->nullable();
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
        Schema::connection('master')->dropIfExists('ajustes');
    }
}
