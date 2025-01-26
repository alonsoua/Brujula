<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCamposToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rut')
                  ->after('password');

            $table->string('nombres')
                  ->after('rut');

            $table->string('primerApellido')
                  ->after('nombres');

            $table->string('segundoApellido')
                  ->after('primerApellido');

            $table->integer('idEstablecimientoActivo')
                  ->after('segundoApellido')
                  ->nullable();

            $table->string('estado')
                  ->after('idEstablecimientoActivo');

            $table->integer('idUsuarioCreated')
                  ->after('remember_token')
                  ->nullable();

            $table->integer('idUsuarioUpdated')
                  ->after('idUsuarioCreated')
                  ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rut');
            $table->dropColumn('nombres');
            $table->dropColumn('primerApellido');
            $table->dropColumn('segundoApellido');
            $table->dropColumn('idEstablecimientoActivo');
            $table->dropColumn('estado');
            $table->dropColumn('idUsuarioCreated');
            $table->dropColumn('idUsuarioUpdated');
        });
    }
}
