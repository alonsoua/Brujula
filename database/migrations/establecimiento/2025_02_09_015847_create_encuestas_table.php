<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEncuestasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->create('encuestas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 90);
            $table->string('descripcion', 250)->nullable();
            $table->enum('tipo', ['Interna', 'Externa'])
                ->comment('Interna, Externa');
            $table->json('roles')->comment('Interna = roles, Externa: estudiantes, apoderados');
            $table->string('imagen')->nullable();
            $table->enum('estado', ['Borrador', 'Publicada', 'Finalizada'])->default('Borrador')
                ->comment('Borrador, Publicada, Finalizada');
            $table->integer('usuario_id')->nullable();
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
        Schema::connection('establecimiento')->dropIfExists('encuestas');
    }
}
