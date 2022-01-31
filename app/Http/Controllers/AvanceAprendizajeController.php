<?php

namespace App\Http\Controllers;

use App\Models\PuntajeIndicador;
use App\Models\PuntajeIndicadorTransformacion;
use App\Models\UsuarioAsignatura;

class AvanceAprendizajeController extends Controller
{
    /**
     * Obtiene los tipo de enseñanza asignados por idUsuarioEstablecimiento
     * * $idUsuarioEstablecimiento
     * @return \Illuminate\Http\Response
     */
    public function getTipoEnseñanza($idUsuarioEstablecimiento) {
        return UsuarioAsignatura::getTipoEnseñanza($idUsuarioEstablecimiento);
    }

    /**
     * Obtiene los cursos asignados al usuario
     * * $idUsuarioEstablecimiento
     * @return \Illuminate\Http\Response
     */
    public function getCursoActivo($idUsuarioEstablecimiento)
    {
        return UsuarioAsignatura::getCursoActivo($idUsuarioEstablecimiento);
    }

    /**
     * Obtiene las asignaturas asignadas al usuario
     * * $idUsuarioEstablecimiento
     * @return \Illuminate\Http\Response
     */
    public function getAsignaturaActiva($idUsuarioEstablecimiento)
    {
        return UsuarioAsignatura::getAsignaturaActiva($idUsuarioEstablecimiento);
    }

}
