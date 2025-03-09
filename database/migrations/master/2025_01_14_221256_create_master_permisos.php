<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterPermisos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('master')->create('master_permisos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('action', 20);
            $table->string('subject', 20);
            $table->unsignedBigInteger('id_rol');
            $table->foreign('id_rol')
                ->references('id')
                ->on('master_roles')

                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('master')->dropIfExists('master_permisos');
    }
}
