<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('master')->create('master_roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre', 80)->unique();
            $table->boolean('estado')->default(true)->comment('true: activo - false: inactivo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('master')->dropIfExists('master_roles');
    }
}
