<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubcategoriasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->create('subcategorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->enum('tipo', ['Interna', 'Externa'])
                ->comment('Interna, Externa');
            $table->foreignId('categoria_id')->constrained()->onDelete('cascade')->nullable();
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo')
                ->comment('Activo, Inactivo');
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
        Schema::connection('establecimiento')->dropIfExists('subcategorias');
    }
}
