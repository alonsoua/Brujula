<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEstabTableAddHostAndPort extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('master')->table('estab', function (Blueprint $table) {
            $table->string('bd_host', 255)->default('127.0.0.1')->after('bd_user');
            $table->string('bd_port', 10)->default('3306')->after('bd_host');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('master')->table('estab', function (Blueprint $table) {
            $table->dropColumn('bd_host');
            $table->dropColumn('bd_port');
        });
    }
}
