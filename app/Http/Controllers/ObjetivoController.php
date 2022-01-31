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
    public function getObjetivosActivosAsignatura($idAsignatura)
    {
        return Objetivo::getObjetivosActivosAsignatura($idAsignatura);
    }

}