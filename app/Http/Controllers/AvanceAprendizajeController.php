<?php

namespace App\Http\Controllers;

use App\Models\PuntajeIndicador;
use App\Models\PuntajeIndicadorTransformacion;
use App\Models\Establecimiento;
use App\Models\UsuarioAsignatura;
use Illuminate\Http\Request;

class AvanceAprendizajeController extends Controller
{
    /**
     * Obtiene los tipo de enseñanza asignados por idUsuarioEstablecimiento
     * * $idUsuarioEstablecimiento
     * @return \Illuminate\Http\Response
     */
    public function getTipoEnseñanza($idUsuarioEstablecimiento)
    {
        return UsuarioAsignatura::getTipoEnseñanza($idUsuarioEstablecimiento);
    }

    /**
     * Obtiene los cursos asignados al usuario
     * * $idUsuarioEstablecimiento
     * @return \Illuminate\Http\Response
     */
    public function getCursoActivo(Request $request, $idUsuarioEstablecimiento, $idPeriodoHistorico)
    {
        $user = $request->user();
        if ($user->idPeriodoActivo === null) {
            $establecimiento = Establecimiento::getAll($user->idEstablecimientoActivo);
            $idPeriodoActivo = $establecimiento[0]['idPeriodoActivo'];
        } else {
            $idPeriodoActivo = $user->idPeriodoActivo;
        }

        $cursos = UsuarioAsignatura::select(
            'cursos.id',
            'cursos.letra',
            'cursos.idProfesorJefe',
            'cursos.idGrado',
            'grados.nombre as nombreGrado',
            'grados.idNivel'
        )
            ->leftJoin("cursos", "cursos.id", "=", "usuario_asignaturas.idCurso")
            ->leftJoin("grados", "cursos.idGrado", "=", "grados.id");
        if ($idPeriodoActivo === $idPeriodoHistorico) {
            $cursos = $cursos->where('usuario_asignaturas.idUsuarioEstablecimiento', $idUsuarioEstablecimiento);
        }
        $cursos = $cursos->where('cursos.estado', 'Activo')
            ->where('cursos.idPeriodo', $idPeriodoHistorico)
            ->orderBy('cursos.idGrado')
            ->orderBy('cursos.letra')
            ->distinct()
            ->get();
        return $cursos;
    }

    /**
     * Obtiene las asignaturas asignadas al usuario
     * * $idUsuarioEstablecimiento
     * @return \Illuminate\Http\Response
     */
    public function getAsignaturaActiva(Request $request, $idUsuarioEstablecimiento, $idPeriodoHistorico)
    {
        $user = $request->user();
        var_dump('idPeriodoHistorico : ', $idPeriodoHistorico);
        if ($user->idPeriodoActivo === null) {
            $establecimiento = Establecimiento::getAll($user->idEstablecimientoActivo);
            $idPeriodoActivo = $establecimiento[0]['idPeriodoActivo'];
        } else {
            $idPeriodoActivo = $user->idPeriodoActivo;
        }

        $cursos = UsuarioAsignatura::select(
            'asignaturas.id',
            'asignaturas.nombre',
            'asignaturas.idGrado',
            'cursos.id as idCurso'
        )
            ->leftJoin("cursos", "cursos.id", "=", "usuario_asignaturas.idCurso")
            // ->leftJoin("asignaturas", "asignaturas.idGrado", "=", "cursos.idGrado")
            ->leftJoin('asignaturas', function ($join) {
                $join->on('asignaturas.id', '=', 'usuario_asignaturas.idAsignatura');
                $join->on('asignaturas.idGrado', '=', 'cursos.idGrado');
            });
        // ->leftJoin("grados", "cursos.idGrado", "=", "grados.id")
        if ($idPeriodoActivo === $idPeriodoHistorico) {
            $cursos = $cursos->where('usuario_asignaturas.idUsuarioEstablecimiento', $idUsuarioEstablecimiento);
        }
        $cursos = $cursos->where('cursos.estado', 'Activo')
            ->where('asignaturas.estado', 'Activo')
            ->where('cursos.idPeriodo', $idPeriodoHistorico)
            ->orderBy('asignaturas.id')
            ->distinct()
            ->get();
        return $cursos;
    }

    /**
     * Obtiene los tipo de enseñanza asignados por idEstablecimiento
     * * $idEstablecimiento
     * @return \Illuminate\Http\Response
     */
    public function getAsignaturaCursoActiva($idCurso)
    {
        return UsuarioAsignatura::getAsignaturaCursoActiva($idCurso);
    }
}
