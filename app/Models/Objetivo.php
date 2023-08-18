<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\IndicadoresPersonalizados;
class Objetivo extends Model
{
    use HasFactory;

    protected $table = "objetivos";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'abreviatura',
        'priorizacion',
        'priorizacionInterna',
        'idEje',
        'idUnidad',
        'estado',
    ];

    public static function getObjetivosAsignatura($idAsignatura) {
        $sql = 'SELECT
                    ob.id
                    , ob.nombre as nombreObjetivo
                    , un.nombre as nombreUnidad
                    , ob.abreviatura
                    , ob.priorizacion
                    , ob.estado
                    , ob.idEje
                FROM unidades as un
                LEFT JOIN objetivos as ob
                    ON ob.idUnidad = un.id
                WHERE
                    un.idAsignatura = '.$idAsignatura.'
                Order By ob.abreviatura';

        return DB::select($sql, []);

    }

    public static function getObjetivosActivosAsignatura($idAsignatura, $idPeriodo) {

        $sql = 'SELECT
                    ob.id
                    , ob.nombre as nombreObjetivo
                    , ej.nombre as nombreEje
                    -- , un.nombre as nombreUnidad
                    , ob.abreviatura
                    , ob.priorizacion
                    , ob.priorizacionInterna
                    , ob.estado
                    , ob.idEje
                -- FROM unidades as un
                -- FROM objetivos as ob
                -- LEFT JOIN unidades as un
                --     ON ob.idUnidad = un.id
                FROM ejes as ej
                LEFT JOIN objetivos as ob
                    ON ob.idEje = ej.id
                WHERE
                    -- un.idAsignatura = '.$idAsignatura.'
                    ej.idAsignatura = '.$idAsignatura.'
                    AND ob.estado = "Activo"
                Order By ob.abreviatura';

        return DB::select($sql, []);

    }

    public static function countObjetivosTrabajados($idObjetivo, $idAsignatura, $idPeriodo, $idCurso, $tipoIndicador) {

        if ($tipoIndicador === 'Normal') {
            $indicadores = Indicador::selectRaw('
                        indicadores.id,
                        indicadores.nombre
            ')
            ->addSelect(['puntajes_indicadores' => PuntajeIndicador::select(DB::raw('count(id) '))
                ->whereColumn('puntajes_indicadores.idIndicador', 'indicadores.id')
                ->where('puntajes_indicadores.idAsignatura', $idAsignatura)
                ->where('puntajes_indicadores.idCurso', $idCurso)
                ->where('puntajes_indicadores.idPeriodo', $idPeriodo)
                ->where('puntajes_indicadores.tipoIndicador', 'Normal')
            ])
            ->where('indicadores.idObjetivo', $idObjetivo)
            ->get();
        } else if ($tipoIndicador === 'Interno') {
            $indicadores = IndicadoresPersonalizados::selectRaw('
                        indicadores_personalizados.id,
                        indicadores_personalizados.nombre
            ')
            ->addSelect(['puntajes_indicadores' => PuntajeIndicador::select(DB::raw('count(id) '))
                ->whereColumn('puntajes_indicadores.idIndicador', 'indicadores_personalizados.id')
                ->where('puntajes_indicadores.idAsignatura', $idAsignatura)
                ->where('puntajes_indicadores.idCurso', $idCurso)
                ->where('puntajes_indicadores.idPeriodo', $idPeriodo)
                ->where('puntajes_indicadores.tipoIndicador', 'Interno')
            ])
            ->where('indicadores_personalizados.idObjetivo', $idObjetivo)
            ->get();
        }

        return $indicadores;
    }


    public static function countObjetivosTrabajadosPersonalizado($idObjetivo, $idAsignatura, $idPeriodo, $idCurso, $tipo) {
        return IndicadorPersonalizado::selectRaw('
                    indicador_personalizados.id,
                    indicador_personalizados.nombre
        ')
        ->addSelect(['puntajes_indicadores' => PuntajeIndicador::select(DB::raw('count(id) '))
            ->whereColumn('puntajes_indicadores.idIndicador', 'indicador_personalizados.id')
            ->where('puntajes_indicadores.idAsignatura', $idAsignatura)
            ->where('puntajes_indicadores.idCurso', $idCurso)
            ->where('puntajes_indicadores.idPeriodo', $idPeriodo)
            ->where('puntajes_indicadores.tipoIndicador', 'Personalizado')
        ])
        ->where('indicador_personalizados.idObjetivo', $idObjetivo)
        ->where('indicador_personalizados.tipo_objetivo', $tipo)
        ->where('indicador_personalizados.estado', 'Aprobado')
        ->get();
    }

    public static function getObjetivosBetwen($idCursoInicio, $idCursoFin) {
    $sql = 'SELECT
                o.id
                , o.nombre as nombreObjetivo
                , e.nombre as nombreEje
                , a.nombre as nombreAsignatura
                , g.nombre as nombreCurso
                , o.abreviatura
                , o.priorizacion
            FROM cursos as c
            LEFT JOIN asignaturas as a
                ON c.idGrado = a.idGrado
            LEFT JOIN ejes as e
                ON a.id = e.idAsignatura
            LEFT JOIN objetivos as o
                ON e.id = o.idEje
            LEFT JOIN grados as g
                ON c.idGrado = g.id
            WHERE
                c.id >= '.$idCursoInicio.' AND
                c.id <= '.$idCursoFin.'
            Order By o.id';

            return DB::select($sql, []);
    }

    public static function getObjetivosMinisterio() {
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
                , o.priorizacion
                , o.priorizacionInterna
                , o.estado -- autorización
                -- , o.priorizaciónInterna -- autorización
            FROM objetivos as o
            LEFT JOIN ejes as e
                ON e.id = o.idEje
            LEFT JOIN asignaturas as a
                ON a.id = e.idAsignatura
            LEFT JOIN grados as g
                ON g.id = a.idGrado
            LEFT JOIN tipo_enseñanza as te
                ON te.idNivel = g.idNivel
            Where o.idEje != "null"
            Order By g.id, a.id, e.id, o.abreviatura';

            return DB::select($sql, []);
    }
}
