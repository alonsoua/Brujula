<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateConexionesFieldInEstabUsuariosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('master')->table('estab_usuarios', function (Blueprint $table) {
            $table->unsignedInteger('conexiones')->default(0)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('master')->table('estab_usuarios', function (Blueprint $table) {
            $table->unsignedInteger('conexiones')->nullable()->default(null)->change();
        });
    }
}
