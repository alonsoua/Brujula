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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getPromedio($cantidadIndicadores, $puntajeObtenido, $idEstablecimiento)
    {
        if ($cantidadIndicadores > 0) {
            $establecimiento = Establecimiento::getAll($idEstablecimiento);
            $notaConversion = NotasConversion::getNotasConversion($cantidadIndicadores, $puntajeObtenido, $establecimiento[0]->idPeriodoActivo, $idEstablecimiento);
        } else {
            $notaConversion = '-';
        }
        return $notaConversion;
    }

    public function getPromedioNota($puntajes)
    {
        // Filtrar puntajes mayores a 0
        $valores = array_filter(array_map(fn($p) => $p->puntaje, $puntajes), fn($v) => $v > 0);

        // Verificar si hay valores válidos
        if (empty($valores)) {
            return 'undefined';
        }

        // Calcular el promedio
        $promedio = array_sum($valores) / count($valores);

        // Formatear el promedio y devolverlo con un decimal
        return number_format($promedio / 10, 1);
    }
}
