<?php

namespace App\Http\Controllers;

use App\Models\Objetivo;
use Illuminate\Http\Request;

class ObjetivoController extends Controller
{
    /**
     * Obtiene objetivos por asignatura con
     * * $idAsignatura
     * @return \Illuminate\Http\Response
     */
    public function getObjetivosActivosAsignatura($idAsignatura, $idPeriodo)
    {
        $objetivos = Objetivo::getObjetivosActivosAsignatura($idAsignatura, $idPeriodo);
        foreach ($objetivos as $key => $objetivo) {
            $objetivo->puntajes_indicadores = 0;
            $objetivo->puntajes_indicadores_personalizado = 0;
            $trabajados_normal = Objetivo::countObjetivosTrabajados($objetivo->id, $idAsignatura, $idPeriodo);
            foreach ($trabajados_normal as $key => $trabajado) {
                $objetivo->puntajes_indicadores += $trabajado->puntajes_indicadores;
            }

            $trabajados_personalizado = Objetivo::countObjetivosTrabajadosPersonalizado($objetivo->id, $idAsignatura, $idPeriodo);
            foreach ($trabajados_personalizado as $key => $trabajado) {
                $objetivo->puntajes_indicadores_personalizado += $trabajado->puntajes_indicadores;
            }
        }
        return $objetivos;
    }

     /**
     * Obtiene objetivos por asignatura con
     * * $idAsignatura
     * @return \Illuminate\Http\Response
     */
    public function getObjetivosBetwen($idCursoInicio, $idCursoFin)
    {
        return Objetivo::getObjetivosBetwen($idCursoInicio, $idCursoFin);
    }
}
