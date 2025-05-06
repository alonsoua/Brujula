<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddColumnsToEvaluaciones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('establecimiento')->table('evaluaciones', function (Blueprint $table) {
            $table->integer('id_evaluacion_ld')->nullable()->after('estado');
            $table->date('fecha_sync')->nullable()->after('id_evaluacion_ld');
            $table->json('log')->nullable()->after('fecha_sync')->comment('Registro de errores durante la sincronización');

            // Agregar la opción 'error' a la columna estado_sync
            DB::statement("ALTER TABLE evaluaciones MODIFY COLUMN estado_sync ENUM('no_sync', 'sync', 'de_sync', 'error') DEFAULT 'no_sync'");
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
            $table->dropColumn('id_evaluacion_ld');
            $table->dropColumn('fecha_sync');
            $table->dropColumn('log');

            // Revertir la modificación de estado_sync eliminando la opción 'error'
            DB::statement("ALTER TABLE evaluaciones MODIFY COLUMN estado_sync ENUM('no_sync', 'sync', 'de_sync') DEFAULT 'no_sync'");
        });
    }
}
