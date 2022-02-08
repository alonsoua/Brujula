<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        return Unidad::selectRaw('
                    objetivos.id
                    , objetivos.nombre as nombreObjetivo
                    , unidades.nombre as nombreUnidad
                    , objetivos.abreviatura
                    , objetivos.priorizacion
                    , objetivos.estado
                    , objetivos.idEje
        ')
        ->leftJoin('objetivos', 'objetivos.idUnidad', '=', 'unidades.id')
        ->leftJoin('indicadores', 'indicadores.idObjetivo', '=', 'objetivos.id')
        ->addSelect(['puntajes_indicadores' => PuntajeIndicador::select(DB::raw('count(id)'))
            ->whereColumn('puntajes_indicadores.idIndicador', 'indicadores.id')
            ->where('puntajes_indicadores.idPeriodo', $idPeriodo)
        ])
        ->where('unidades.idAsignatura', $idAsignatura)
        ->orderBy('objetivos.abreviatura')
        ->distinct()

        ->get();

        // return Cliente::addSelect(['countSalidas' => Salida::select(DB::raw('count(id)'))
        //         ->whereColumn('idCliente', 'clientes.id')
        //     ])
        // ->addSelect(['estadoCount' => Salida::select(DB::raw('count(id) = 0'))
        //     ->whereColumn('idCliente', 'clientes.id')
        // ])
        // ->orderBy('nombre')
        // ->get();

        // $sql = 'SELECT
        //             ob.id
        //             , ob.nombre as nombreObjetivo
        //             , un.nombre as nombreUnidad
        //             , ob.abreviatura
        //             , ob.priorizacion
        //             , ob.estado
        //             , ob.idEje
        //         FROM unidades as un
        //         LEFT JOIN objetivos as ob
        //             ON ob.idUnidad = un.id
        //         WHERE
        //             un.idAsignatura = '.$idAsignatura.'
        //             AND ob.estado = "Activo"
        //         Order By ob.abreviatura';

        // return DB::select($sql, []);

    }

}
