<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoToRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('master')->table('roles', function (Blueprint $table) {
            $table->enum('tipo', ['Interno', 'Externo'])->nullable()->after('name')->comment('Define si es Interno o Externo a brújula');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('master')->table('roles', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
}
