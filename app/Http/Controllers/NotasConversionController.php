<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotasConversion;
use App\Models\Establecimiento;

class NotasConversionController extends Controller
{

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getNotasConversion(Request $request, $cantidadIndicadores, $puntajeObtenido)
    {
        if ($cantidadIndicadores > 0 && $puntajeObtenido > 0) {
            $user = $request->user();
            $establecimiento = Establecimiento::getAll($user->idEstablecimientoActivo);
            $notaConversion = NotasConversion::getNotasConversion($cantidadIndicadores, $puntajeObtenido, $establecimiento[0]->idPeriodoActivo, $user->idEstablecimientoActivo);
        } else {
            $notaConversion = '-';
        }
        return $notaConversion;
    }
}
