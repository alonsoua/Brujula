<?php

namespace App\Http\Controllers;

use App\Models\IndicadoresPersonalizados;
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
    public function getObjetivosActivosAsignatura(Request $request, $idAsignatura, $idPeriodo)
    {
        $user = $request->user();
        $objetivos = Objetivo::getObjetivosActivosAsignatura($idAsignatura, $idPeriodo);
        $objetivos_personalizados = ObjetivoPersonalizado::getObjetivosActivosAsignatura($idAsignatura, $user->idEstablecimientoActivo);
        foreach ($objetivos as $key => $objetivo) {
            $objetivo->puntajes_indicadores = 0;
            $objetivo->puntajes_indicadores_personalizado = 0;
            $objetivo->tipo = 'Ministerio';
            $trabajados_normal = Objetivo::countObjetivosTrabajados($objetivo->id, $idAsignatura, $idPeriodo, 'Normal');
            foreach ($trabajados_normal as $key => $trabajado) {
                $objetivo->puntajes_indicadores += $trabajado->puntajes_indicadores;
            }

            $trabajados_personalizado = Objetivo::countObjetivosTrabajadosPersonalizado($objetivo->id, $idAsignatura, $idPeriodo, 'Ministerio');
            foreach ($trabajados_personalizado as $key => $trabajado) {
                $objetivo->puntajes_indicadores_personalizado += $trabajado->puntajes_indicadores;
            }
        }
        foreach ($objetivos_personalizados as $key => $objetivo) {
            $objetivo->puntajes_indicadores = 0;
            $objetivo->puntajes_indicadores_personalizado = 0;
            $objetivo->priorizacion = null;
            $objetivo->tipo = 'Interno';
            $trabajados_normal = Objetivo::countObjetivosTrabajados($objetivo->id, $idAsignatura, $idPeriodo, 'Interno');
            foreach ($trabajados_normal as $key => $trabajado) {
                $objetivo->puntajes_indicadores += $trabajado->puntajes_indicadores;
            }

            $trabajados_personalizado = Objetivo::countObjetivosTrabajadosPersonalizado($objetivo->id, $idAsignatura, $idPeriodo, 'Interno');
            foreach ($trabajados_personalizado as $key => $trabajado) {
                $objetivo->puntajes_indicadores_personalizado += $trabajado->puntajes_indicadores;
            }

            array_push($objetivos, $objetivo);
        }

        return $objetivos;
    }

    /**
     * Obtiene objetivos por asignatura con
     * * $idAsignatura
     * @return \Illuminate\Http\Response
     */
    public function getObjetivos(Request $request)
    {
        $user = $request->user();
        $objetivos = Objetivo::getObjetivosMinisterio();
        $objetivos_personalizados = ObjetivoPersonalizado::getObjetivosPersonalizados($user->idEstablecimientoActivo);
        foreach ($objetivos_personalizados as $key => $objetivo) {
            array_push($objetivos, $objetivo);
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storePersonalizado(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $objetivo_personalizado = ObjetivoPersonalizado::Create([
                    'nombre'            => $request['nombre'],
                    'abreviatura'       => $request['abreviatura'],
                    'priorizacion'      => $request['priorizacion'],
                    'idEje'             => $request['idEje'],
                    'idEstablecimiento' => $request['idEstablecimiento'],
                    'estado'            => $request['estado'],
                ]);

                foreach ($request['indicadores'] as $key => $indicador) {
                    IndicadoresPersonalizados::Create([
                        'nombre'     => $indicador['nombre'],
                        'idObjetivo' => $objetivo_personalizado->id,
                        'estado'     => 'Activo',
                    ]);
                }

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

            $indicadores = IndicadoresPersonalizados::select('id as idIndicador', 'nombre')
                                        ->where('idObjetivo',$id)->get();
            // Pasa a array el get
            $indicadores_eliminar = Array();
            foreach ($indicadores as $key => $indicador) {
                array_push($indicadores_eliminar, $indicador->idIndicador);
            }

            foreach ($request['indicadores'] as $key => $indicador) {
                if (isset($indicador['idIndicador'])) {
                    $indicador_personalizado = IndicadoresPersonalizados::findOrFail($indicador['idIndicador']);
                    $indicador_personalizado->nombre = $indicador['nombre'];
                    $indicador_personalizado->save();
                    if (($key = array_search($indicador['idIndicador'], $indicadores_eliminar)) !== false) {
                        unset($indicadores_eliminar[$key]);
                    }
                } else {
                    IndicadoresPersonalizados::Create([
                        'nombre'     => $indicador['nombre'],
                        'idObjetivo' => $id,
                        'estado'     => 'Activo',
                    ]);
                }
            }

            foreach ($indicadores_eliminar as $key => $idIndicador) {
                $indicador_eliminar = IndicadoresPersonalizados::findOrFail($idIndicador);
                $indicador_eliminar->estado = 'Inactivo';
                $indicador_eliminar->save();
            }

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

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updatePriorizacionInterna(Request $request, $id)
    {
        try {

            $objetivo = Objetivo::findOrFail($id);

            $objetivo->priorizacionInterna = $request['priorizacionInterna'];
            $objetivo->save();

            return response('success', 200);
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }
}
