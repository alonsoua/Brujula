<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuntajeIndicador extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
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
        'tipoIndicador',
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
        $idObjetivo,
        $tipoIndicador
    ) {
        if ($tipoIndicador === 'Ministerio') {
            $puntajeIndicador = PuntajeIndicador::select(
                'puntajes_indicadores.id'
                , 'puntajes_indicadores.idIndicador'
                , 'puntajes_indicadores.idAlumno'
                , 'puntajes_indicadores.puntaje'
                , 'puntajes_indicadores.tipoIndicador'
                , 'puntajes_indicadores.estado'
                , 'puntajes_indicadores.idUsuario_created'
                , 'puntajes_indicadores.idUsuario_updated'
                , 'puntajes_indicadores.created_at'
                , 'puntajes_indicadores.updated_at'
                , 'indicadores.idObjetivo as idObjetivoIndicador'
                , 'indicador_personalizados.idObjetivo as idObjetivoIndicadorPersonalizado'
                , 'indicador_personalizados.tipo_objetivo'
            )
            ->leftJoin("indicadores",
                "puntajes_indicadores.idIndicador", "=", "indicadores.id"
            )
            ->leftJoin("indicador_personalizados",
                "puntajes_indicadores.idIndicador", "=", "indicador_personalizados.id"
            )
            ->where('puntajes_indicadores.idPeriodo', $idPeriodo)
            ->where('puntajes_indicadores.idCurso', $idCurso)
            ->where('puntajes_indicadores.idAsignatura', $idAsignatura)
            ->where('puntajes_indicadores.tipoIndicador', 'Normal')
            ->where('puntajes_indicadores.estado', 'Activo')
            ->where('indicadores.idObjetivo', $idObjetivo)
            ->orWhere('indicador_personalizados.tipo_objetivo', 'Ministerio')
            ->orWhere('indicador_personalizados.idObjetivo', $idObjetivo)
            ->orWhere('indicador_personalizados.estado', 'Aprobado')
            ->get();
        }
        else if ($tipoIndicador === 'Interno') {
            $puntajeIndicador = PuntajeIndicador::select(
                'puntajes_indicadores.id'
                , 'puntajes_indicadores.idIndicador'
                , 'puntajes_indicadores.idAlumno'
                , 'puntajes_indicadores.puntaje'
                , 'puntajes_indicadores.tipoIndicador'
                , 'puntajes_indicadores.estado'
                , 'puntajes_indicadores.idUsuario_created'
                , 'puntajes_indicadores.idUsuario_updated'
                , 'puntajes_indicadores.created_at'
                , 'puntajes_indicadores.updated_at'
                , 'indicadores_personalizados.idObjetivo as idObjetivoIndicador'
                , 'indicador_personalizados.idObjetivo as idObjetivoIndicadorPersonalizado'
                , 'indicador_personalizados.tipo_objetivo'
            )
            ->leftJoin("indicadores_personalizados",
                "puntajes_indicadores.idIndicador", "=", "indicadores_personalizados.id"
            )
            ->leftJoin("indicador_personalizados",
                "puntajes_indicadores.idIndicador", "=", "indicador_personalizados.id"
            )
            ->where('puntajes_indicadores.idPeriodo', $idPeriodo)
            ->where('puntajes_indicadores.idCurso', $idCurso)
            ->where('puntajes_indicadores.idAsignatura', $idAsignatura)
            ->where('puntajes_indicadores.tipoIndicador', 'Interno')
            ->where('puntajes_indicadores.estado', 'Activo')
            ->where('indicadores_personalizados.idObjetivo', $idObjetivo)
            ->orWhere('indicador_personalizados.tipo_objetivo', 'Interno')
            ->orWhere('indicador_personalizados.idObjetivo', $idObjetivo)
            ->orWhere('indicador_personalizados.estado', 'Aprobado')
            ->get();
        }
        // return response($puntajeIndicador, 200);
        return $puntajeIndicador;
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
                , 'puntajes_indicadores.tipoIndicador'
                , 'puntajes_indicadores.estado'
                , 'puntajes_indicadores.idUsuario_created'
                , 'puntajes_indicadores.idUsuario_updated'
                , 'puntajes_indicadores.created_at'
                , 'puntajes_indicadores.updated_at'
            )
            ->leftJoin("indicadores",
                "puntajes_indicadores.idIndicador", "=", "indicadores.id"
            )
            ->leftJoin("indicador_personalizados",
                "puntajes_indicadores.idIndicador", "=", "indicador_personalizados.id"
            )
            ->where('puntajes_indicadores.idPeriodo', $idPeriodo)
            ->where('puntajes_indicadores.idAlumno', $idAlumno)
            ->where('puntajes_indicadores.idAsignatura', $idAsignatura)
            ->where('indicadores.idObjetivo', $idObjetivo)
            ->where('puntajes_indicadores.tipoIndicador', 'Normal')
            ->where('puntajes_indicadores.estado', 'Activo')
            // ->where('indicador_personalizados.idObjetivo', $idObjetivo)
            ->get();
    }

    public static function getPuntajesAlumnoPersonalizado(
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
                , 'puntajes_indicadores.tipoIndicador'
                , 'puntajes_indicadores.estado'
                , 'puntajes_indicadores.idUsuario_created'
                , 'puntajes_indicadores.idUsuario_updated'
                , 'puntajes_indicadores.created_at'
                , 'puntajes_indicadores.updated_at'
            )
            ->leftJoin("indicador_personalizados",
                "puntajes_indicadores.idIndicador", "=", "indicador_personalizados.id"
            )
            ->where('puntajes_indicadores.idPeriodo', $idPeriodo)
            ->where('puntajes_indicadores.idAlumno', $idAlumno)
            ->where('puntajes_indicadores.idAsignatura', $idAsignatura)
            ->where('indicador_personalizados.idObjetivo', $idObjetivo)
            ->where('puntajes_indicadores.tipoIndicador', 'Personalizado')
            ->where('puntajes_indicadores.estado', 'Activo')
            ->where('indicador_personalizados.estado', 'Aprobado')
            ->get();
    }

    public static function findPuntajeIndicador(
        $idPeriodo,
        $idCurso,
        $idAsignatura,
        $idIndicador,
        $idAlumno,
        $tipoIndicador
    ) {
        return PuntajeIndicador::select('puntajes_indicadores.id')
            ->where('puntajes_indicadores.idPeriodo', $idPeriodo)
            ->where('puntajes_indicadores.idCurso', $idCurso)
            ->where('puntajes_indicadores.idAsignatura', $idAsignatura)
            ->where('puntajes_indicadores.idIndicador', $idIndicador)
            ->where('puntajes_indicadores.idAlumno', $idAlumno)
            ->where('puntajes_indicadores.tipoIndicador', $tipoIndicador)
            ->where('puntajes_indicadores.estado', 'Activo')
            ->get();
    }

    public static function findIndicadorPersonalizados($idIndicador) {
        return PuntajeIndicador::select('puntajes_indicadores.id')
            ->where('puntajes_indicadores.idIndicador', $idIndicador)
            ->where('puntajes_indicadores.tipoIndicador', 'Personalizado')
            ->where('puntajes_indicadores.estado', 'Activo')
            ->get();
    }
}
