<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Asignatura extends Model
{
    use HasFactory;

    protected $table = "asignaturas";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'idGrado',
        'estado',
    ];

    public static function getAll() {
        $sql = 'SELECT
                    asi.id
                    , asi.nombre as nombreAsignatura
                    , gr.nombre as nombreGrado
                FROM asignaturas as asi
                LEFT JOIN grados as gr
                    ON asi.idGrado = gr.id
                ORDER BY asi.id';

        return DB::select($sql, []);

    }

    public static function getAllGrado($idGrado) {
        $asignaturas = Asignatura::select(
                        'asignaturas.id'
                        , 'asignaturas.nombre as nombreAsignatura'
                        , 'grados.id as idGrado'
                        , 'grados.nombre as nombreGrado'
                    )
            ->leftJoin("grados", "asignaturas.idGrado", "=", "grados.id")
            ->where('asignaturas.idGrado', $idGrado)
            ->where('asignaturas.estado', 'Activo')
            ->orderBy('asignaturas.id')
            ->get();

        return $asignaturas;
    }

}
