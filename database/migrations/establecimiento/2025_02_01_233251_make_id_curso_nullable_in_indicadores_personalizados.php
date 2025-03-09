<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeIdCursoNullableInIndicadoresPersonalizados extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE indicadores_personalizados MODIFY COLUMN idCurso BIGINT UNSIGNED NULL;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE indicadores_personalizados MODIFY COLUMN idCurso BIGINT UNSIGNED NOT NULL;");
    }
}
