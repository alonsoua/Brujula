<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEstadoSyncToEvaluaciones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->table('evaluaciones', function (Blueprint $table) {
            $table->enum('estado_sync', ['no_sync', 'sync', 'de_sync'])
                ->after('estado')
                ->comment('
                no_sync: Evaluaciones nuevas no sincronizadas. 
                sync: Evaluaciones sincronizadas a LD. 
                de_sync: Evaluaciones desincronizadas de LD.');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('establecimiento')->table('evaluaciones', function (Blueprint $table) {
            $table->dropColumn('estado_sync');
        });
    }
}
