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
            $table->enum('estado_sync', ['new', 'sync', 'update'])
                ->after('estado')
                ->comment('new: Evaluaciones nuevas con o sin notas. sync: Sincronizado a LD. update: Se actualizó por lo que se debe volver a sincronizar.');
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
