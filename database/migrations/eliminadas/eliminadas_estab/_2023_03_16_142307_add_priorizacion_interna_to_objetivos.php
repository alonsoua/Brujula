<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriorizacionInternaToObjetivos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('objetivos', function (Blueprint $table) {
            $table->string('priorizacionInterna')
                    ->after('priorizacion')
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
        Schema::table('objetivos', function (Blueprint $table) {
            $table->dropColumn('priorizacionInterna');
        });
    }
}
