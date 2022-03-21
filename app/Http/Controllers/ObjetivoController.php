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
            $trabajados = Objetivo::countObjetivosTrabajados($objetivo->id, $idAsignatura, $idPeriodo);
            foreach ($trabajados as $key => $trabajado) {
                $objetivo->puntajes_indicadores += $trabajado->puntajes_indicadores;
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
