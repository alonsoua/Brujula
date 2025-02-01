<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNombrePrioritarioToAlumnosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->table('alumnos', function (Blueprint $table) {
            $table->string('nombre_prioritario')->after('idPrioritario')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('establecimiento')->table('alumnos', function (Blueprint $table) {
            $table->dropColumn('nombre_prioritario');
        });
    }
}
