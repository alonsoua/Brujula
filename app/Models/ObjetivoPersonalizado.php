<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ObjetivoPersonalizado extends Model
{
    use HasFactory;
    protected $connection = 'cliente';
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

    public static function getObjetivosActivosAsignatura($idAsignatura, $idEstablecimiento) {

        $sql = 'SELECT
                    ob.id
                    , ob.nombre as nombreObjetivo
                    , ej.nombre as nombreEje
                    -- , un.nombre as nombreUnidad
                    , ob.abreviatura
                    , ob.priorizacion as priorizacionInterna
                    , ob.idEstablecimiento
                    , ob.estado
                    , ob.idEje
                -- FROM unidades as un
                -- FROM objetivos as ob
                -- LEFT JOIN unidades as un
                --     ON ob.idUnidad = un.id
                FROM ejes as ej
                LEFT JOIN objetivos_personalizados as ob
                    ON ob.idEje = ej.id
                WHERE
                    -- un.idAsignatura = '.$idAsignatura.'
                    ej.idAsignatura = '.$idAsignatura.'
                    AND ob.idEstablecimiento = '.$idEstablecimiento.'
                    AND ob.estado = "Activo"
                Order By ob.abreviatura';

        return DB::select($sql, []);

    }

    public static function getObjetivosPersonalizados($id_establecimiento) {
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
                , o.priorizacion as priorizacionInterna
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
