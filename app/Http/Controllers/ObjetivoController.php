<?php

namespace App\Http\Controllers;

use App\Models\IndicadoresPersonalizados;
use App\Models\Indicador;
use App\Models\PuntajeIndicador;
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
        $sql_oobjetivos =
            'SELECT
                ob.id
                , ob.nombre as nombreObjetivo
                , ej.nombre as nombreEje
                , ob.abreviatura
                , ob.priorizacion
                , ob.priorizacionInterna
                , ob.estado
                , ob.idEje
            FROM ejes as ej
            LEFT JOIN objetivos as ob
                ON ob.idEje = ej.id
            WHERE
                ej.idAsignatura = ' . $idAsignatura . ' AND
                ob.estado = "Activo"
            Order By ob.abreviatura
            ';

        $objetivos = DB::select($sql_oobjetivos, []);

        $sql_oobjetivos_personalizados =
            'SELECT
                ob.id
                , ob.nombre as nombreObjetivo
                , ej.nombre as nombreEje
                , ob.abreviatura
                , ob.priorizacion as priorizacionInterna
                , ob.idEstablecimiento
                , ob.estado
                , ob.idEje
            FROM ejes as ej
            LEFT JOIN objetivos_personalizados as ob
                ON ob.idEje = ej.id
            WHERE
                ej.idAsignatura = ' . $idAsignatura . ' AND
                ob.idEstablecimiento = ' . $user->idEstablecimientoActivo . ' AND
                ob.estado = "Activo"
            Order By ob.abreviatura
            ';

        $objetivos_personalizados = DB::select($sql_oobjetivos_personalizados, []);

        foreach ($objetivos as $key => $objetivo) {
            $objetivo->puntajes_indicadores = 0;
            $objetivo->puntajes_indicadores_personalizado = 0;
            $objetivo->tipo = 'Ministerio';
        }
        foreach ($objetivos_personalizados as $key => $objetivo_personalizado) {
            $objetivo_personalizado->puntajes_indicadores = 0;
            $objetivo_personalizado->puntajes_indicadores_personalizado = 0;
            $objetivo_personalizado->priorizacion = null;
            $objetivo_personalizado->tipo = 'Interno';
            array_push($objetivos, $objetivo_personalizado);
        }

        return $objetivos;
    }

    public function getObjetivosActivosAsignaturaEstablecimiento($idEstablecimientoActivo, $idAsignatura)
    {
        $sql_oobjetivos =
            'SELECT
                ob.id
                , ob.nombre as nombreObjetivo
                , ej.nombre as nombreEje
                , ob.abreviatura
                , ob.priorizacion
                , ob.priorizacionInterna
                , ob.estado
                , ob.idEje
            FROM ejes as ej
            LEFT JOIN objetivos as ob
                ON ob.idEje = ej.id
            WHERE
                ej.idAsignatura = ' . $idAsignatura . ' AND
                ob.estado = "Activo"
            Order By ob.abreviatura
            ';

        $objetivos = DB::select($sql_oobjetivos, []);

        $sql_oobjetivos_personalizados =
            'SELECT
                ob.id
                , ob.nombre as nombreObjetivo
                , ej.nombre as nombreEje
                , ob.abreviatura
                , ob.priorizacion as priorizacionInterna
                , ob.idEstablecimiento
                , ob.estado
                , ob.idEje
            FROM ejes as ej
            LEFT JOIN objetivos_personalizados as ob
                ON ob.idEje = ej.id
            WHERE
                ej.idAsignatura = ' . $idAsignatura . ' AND
                ob.idEstablecimiento = ' . $idEstablecimientoActivo . ' AND
                ob.estado = "Activo"
            Order By ob.abreviatura
            ';

        $objetivos_personalizados = DB::select($sql_oobjetivos_personalizados, []);

        foreach ($objetivos as $key => $objetivo) {
            $objetivo->puntajes_indicadores = 0;
            $objetivo->puntajes_indicadores_personalizado = 0;
            $objetivo->tipo = 'Ministerio';
        }
        foreach ($objetivos_personalizados as $key => $objetivo_personalizado) {
            $objetivo_personalizado->puntajes_indicadores = 0;
            $objetivo_personalizado->puntajes_indicadores_personalizado = 0;
            $objetivo_personalizado->priorizacion = null;
            $objetivo_personalizado->tipo = 'Interno';
            array_push($objetivos, $objetivo_personalizado);
        }

        return $objetivos;
    }

    /**
     * Obtiene objetivos por asignatura con
     * * $idAsignatura
     * * $tipoObjetivo
     * @return \Illuminate\Http\Response
     */
    public function objetivosTrabajados(Request $request)
    {

        try {
            $idPeriodo = $request->idPeriodo;
            $idAsignatura = $request->idAsignatura;
            $objetivos = $request->objetivos;
            $idCurso = $request->idCurso;
            $objetivos_trabajados = array();
            foreach ($objetivos as $key => $objetivo) {
                $objetivo['puntajes_indicadores'] = 0;
                $objetivo['puntajes_indicadores_personalizado'] = 0;
                if ($objetivo['tipo'] === 'Ministerio') {
                    $trabajados_normal = Objetivo::countObjetivosTrabajados($objetivo['id'], $idAsignatura, $idPeriodo, $idCurso, 'Normal');

                    foreach ($trabajados_normal as $key => $trabajado) {
                        $objetivo['puntajes_indicadores'] += (int)$trabajado['puntajes_indicadores'];
                    }
                    $trabajados_personalizado = Objetivo::countObjetivosTrabajadosPersonalizado($objetivo['id'], $idAsignatura, $idPeriodo, $idCurso, $objetivo['tipo']);
                    foreach ($trabajados_personalizado as $key => $trabajado) {
                        $objetivo['puntajes_indicadores_personalizado'] += (int)$trabajado['puntajes_indicadores'];
                    }
                } else if ($objetivo['tipo'] === 'Interno') {
                    $trabajados_normal = Objetivo::countObjetivosTrabajados($objetivo['id'], $idAsignatura, $idPeriodo, $idCurso, $objetivo['tipo']);
                    foreach ($trabajados_normal as $key => $trabajado) {
                        $objetivo['puntajes_indicadores'] += (int)$trabajado['puntajes_indicadores'];
                    }
                    $trabajados_personalizado = Objetivo::countObjetivosTrabajadosPersonalizado($objetivo['id'], $idAsignatura, $idPeriodo, $idCurso, $objetivo['tipo']);
                    foreach ($trabajados_personalizado as $key => $trabajado) {
                        $objetivo['puntajes_indicadores_personalizado'] += (int)$trabajado['puntajes_indicadores'];
                    }
                }
                if ($objetivo['puntajes_indicadores'] !== 0 || $objetivo['puntajes_indicadores_personalizado'] !== 0) {
                    array_push($objetivos_trabajados, array(
                        'id' => $objetivo['id'],
                        'puntajes_indicadores' => $objetivo['puntajes_indicadores'],
                        'puntajes_indicadores_personalizado' => $objetivo['puntajes_indicadores_personalizado'],
                    ));
                }
            }
            if ($objetivos_trabajados != null) {
                return response()->json($objetivos_trabajados);
            }
            return response()->json(['status' => 'error', 'message' => 'Registro no existe'], 404);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
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
                ->where('idObjetivo', $id)->get();
            // Pasa a array el get
            $indicadores_eliminar = array();
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
