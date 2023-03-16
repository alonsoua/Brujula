<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ObjetivoPersonalizado extends Model
{
    use HasFactory;

    use HasFactory;

    protected $table = "objetivos_personalizados";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'abreviatura',
        'priorizacion',
        'idEje',
        'idUnidad',
        'idEstablecimiento',
        'estado',
    ];

    public static function getObjetivosEstablecimiento($id_establecimiento) {
        $sql = 'SELECT
                o.id
                , te.id as idNivel
                , o.nombre as nombreObjetivo
                , e.id as idEje
                , e.nombre as nombreEje
                , a.id as idAsignatura
                , a.nombre as nombreAsignatura
                , g.id as idGrado
                , g.nombre as nombreCurso
                , o.abreviatura
                , o.priorizacion as priorizacionEstablecimiento
                , o.idEstablecimiento
                , o.estado -- autorización
                -- , o.priorizaciónInterna -- autorización
            FROM objetivos_personalizados as o
            LEFT JOIN ejes as e
                ON e.id = o.idEje
            LEFT JOIN asignaturas as a
                ON a.id = e.idAsignatura
            LEFT JOIN grados as g
                ON g.id = a.idGrado
            LEFT JOIN tipo_enseñanza as te
                ON te.idNivel = g.idNivel
            Where o.idEje != "null" and
            o.idEstablecimiento = '.$id_establecimiento.'
            Order By g.id, a.id, e.id, o.abreviatura';

            return DB::select($sql, []);
    }
}
