<?php

namespace App\Http\Controllers;

use App\Models\Indicador;
use App\Models\IndicadoresPersonalizados;
use Illuminate\Http\Request;

class IndicadorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndicadoresObjetivo($idObjetivo, $tipo)
    {
        if ($tipo === 'Ministerio') {
            $indicadores = Indicador::getIndicadoresobjetivo($idObjetivo);
        } else if ($tipo === 'Interno') {
            $indicadores = IndicadoresPersonalizados::getIndicadoresobjetivo($idObjetivo);
        }

        foreach ($indicadores as $key => $indicador) {
            $indicador->tipo = $tipo;
        }

        return $indicadores;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function getIndicadoresPersonalizados($idObjetivo)
    // {
    //     return IndicadoresPersonalizados::getIndicadorespersonalizados($idObjetivo);
    // }
}
