<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuntajeIndicador extends Model
{
    use HasFactory;

    protected $table = "puntajes_indicadores";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'idPeriodo',
        'idCurso',
        'idAsignatura',
        'idIndicador',
        'idAlumno',
        'puntaje',
        'estado',
        'idUsuario_created',
        'idUsuario_updated',
        'created_at',
        'updated_at',
    ];

    public static function getPuntajesIndicadores(
        $idPeriodo,
        $idCurso,
        $idAsignatura,
        $idObjetivo
    ) {
        return PuntajeIndicador::select(
                'puntajes_indicadores.id'
                , 'puntajes_indicadores.idIndicador'
                , 'puntajes_indicadores.idAlumno'
                , 'puntajes_indicadores.puntaje'
                , 'puntajes_indicadores.estado'
                , 'puntajes_indicadores.idUsuario_created'
                , 'puntajes_indicadores.idUsuario_updated'
                , 'puntajes_indicadores.created_at'
                , 'puntajes_indicadores.updated_at'
            )
            ->leftJoin("indicadores",
                "puntajes_indicadores.idIndicador", "=", "indicadores.id"
            )
            ->where('puntajes_indicadores.idPeriodo', $idPeriodo)
            ->where('puntajes_indicadores.idCurso', $idCurso)
            ->where('puntajes_indicadores.idAsignatura', $idAsignatura)
            ->where('indicadores.idObjetivo', $idObjetivo)
            ->get();
    }

    public static function getPuntajesAlumno(
        $idPeriodo,
        $idAlumno,
        $idAsignatura,
        $idObjetivo
    ) {
        return PuntajeIndicador::select(
                'puntajes_indicadores.id'
                , 'puntajes_indicadores.idIndicador'
                , 'puntajes_indicadores.idAlumno'
                , 'puntajes_indicadores.puntaje'
                , 'puntajes_indicadores.estado'
                , 'puntajes_indicadores.idUsuario_created'
                , 'puntajes_indicadores.idUsuario_updated'
                , 'puntajes_indicadores.created_at'
                , 'puntajes_indicadores.updated_at'
            )
            ->leftJoin("indicadores",
                "puntajes_indicadores.idIndicador", "=", "indicadores.id"
            )
            ->where('puntajes_indicadores.idPeriodo', $idPeriodo)
            ->where('puntajes_indicadores.idAlumno', $idAlumno)
            ->where('puntajes_indicadores.idAsignatura', $idAsignatura)
            ->where('indicadores.idObjetivo', $idObjetivo)
            ->get();
    }

    public static function findPuntajeIndicador(
        $idPeriodo,
        $idCurso,
        $idAsignatura,
        $idIndicador,
        $idAlumno
    ) {
        return PuntajeIndicador::select('puntajes_indicadores.id')
            ->where('puntajes_indicadores.idPeriodo', $idPeriodo)
            ->where('puntajes_indicadores.idCurso', $idCurso)
            ->where('puntajes_indicadores.idAsignatura', $idAsignatura)
            ->where('puntajes_indicadores.idIndicador', $idIndicador)
            ->where('puntajes_indicadores.idAlumno', $idAlumno)
            ->get();
    }


}
