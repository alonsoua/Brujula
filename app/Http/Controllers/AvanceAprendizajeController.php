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
    public function getCursoActivo(Request $request, $idUsuarioEstablecimiento)
    {
        $user = $request->user();
        $idPeriodo = $user->idPeriodoActivo;
        if ($idPeriodo === null) {
            $establecimiento = Establecimiento::getAll($user->idEstablecimientoActivo);
            $idPeriodo = $establecimiento[0]['idPeriodoActivo'];
        }
        return UsuarioAsignatura::getCursoActivo($idUsuarioEstablecimiento, $idPeriodo);
    }

    /**
     * Obtiene las asignaturas asignadas al usuario
     * * $idUsuarioEstablecimiento
     * @return \Illuminate\Http\Response
     */
    public function getAsignaturaActiva(Request $request, $idUsuarioEstablecimiento)
    {
        $user = $request->user();
        $idPeriodo = $user->idPeriodoActivo;
        if ($idPeriodo === null) {
            $establecimiento = Establecimiento::getAll($user->idEstablecimientoActivo);
            $idPeriodo = $establecimiento[0]['idPeriodoActivo'];
        }
        return UsuarioAsignatura::getAsignaturaActiva($idUsuarioEstablecimiento, $idPeriodo);
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
