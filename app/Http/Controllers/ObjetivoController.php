<?php

namespace App\Http\Controllers;

use App\Models\Objetivo;
use App\Models\ObjetivoPersonalizado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    /**
     * Obtiene objetivos por asignatura con
     * * $idAsignatura
     * @return \Illuminate\Http\Response
     */
    public function getObjetivosMinisterio()
    {
        return Objetivo::getObjetivosMinisterio();
    }

    /**
     * Obtiene objetivos por asignatura con
     * * $idAsignatura
     * @return \Illuminate\Http\Response
     */
    public function getObjetivosEstablecimiento($id_establecimiento)
    {
        return ObjetivoPersonalizado::getObjetivosEstablecimiento($id_establecimiento);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storePersonalizado(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                ObjetivoPersonalizado::Create([
                    'nombre'            => $request['nombre'],
                    'abreviatura'       => $request['abreviatura'],
                    'priorizacion'      => $request['priorizacion'],
                    'idEje'             => $request['idEje'],
                    'idEstablecimiento' => $request['idEstablecimiento'],
                    'estado'            => $request['estado'],
                ]);

                return response(null, 200);
            });

        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updatePersonalizado(Request $request, $id)
    {
        try {

            $objetivo_personalizado = ObjetivoPersonalizado::findOrFail($id);
            $objetivo_personalizado->nombre = $request['nombre'];
            $objetivo_personalizado->abreviatura = $request['abreviatura'];
            $objetivo_personalizado->priorizacion = $request['priorizacion'];
            $objetivo_personalizado->idEje = $request['idEje'];
            $objetivo_personalizado->idEstablecimiento = $request['idEstablecimiento'];
            $objetivo_personalizado->estado = $request['estado'];
            $objetivo_personalizado->save();

            return response('success', 200);
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateEstadoMinisterio(Request $request, $id)
    {
        try {

            $objetivo = Objetivo::findOrFail($id);

            $objetivo->estado = $request['estado'];
            $objetivo->save();

            return response('success', 200);
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateEstadoPersonalizado(Request $request, $id)
    {
        try {

            $objetivo_personalizado = ObjetivoPersonalizado::findOrFail($id);

            $objetivo_personalizado->estado = $request['estado'];
            $objetivo_personalizado->save();

            return response('success', 200);
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updatePriorizacionPersonalizado(Request $request, $id)
    {
        try {

            $objetivo_personalizado = ObjetivoPersonalizado::findOrFail($id);

            $objetivo_personalizado->priorizacion = $request['priorizacion'];
            $objetivo_personalizado->save();

            return response('success', 200);
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }




}
