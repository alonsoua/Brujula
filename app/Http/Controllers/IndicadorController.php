<?php

namespace App\Http\Controllers;

use App\Models\Indicador;
use Illuminate\Http\Request;

class IndicadorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndicadoresObjetivo($idObjetivo)
    {
        return Indicador::getIndicadoresobjetivo($idObjetivo);
    }
}
