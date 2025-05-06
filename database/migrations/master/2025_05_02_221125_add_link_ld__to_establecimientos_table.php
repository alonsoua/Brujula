<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLinkLdToEstablecimientosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('master')->table('establecimientos', function (Blueprint $table) {
            $table->string('link_ld', 50)->nullable()->after('estado');
            $table->string('user_ld', 13)->nullable()->after('link_ld');
            $table->string('pass_ld', 255)->nullable()->after('user_ld');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('master')->table('establecimientos', function (Blueprint $table) {
            $table->dropColumn(['link_ld', 'user_ld', 'pass_ld']);
        });
    }
}
