<?php

namespace App\Http\Controllers;

use App\Models\IndicadoresPersonalizados;
use App\Models\Indicador;
use App\Models\Objetivo;
use App\Models\PuntajeIndicador;
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

    public function getObjetivosActivosAsignatura(Request $request, $idAsignatura)
    {
        $objetivos = Objetivo::with(['eje:id,nombre'])
        ->select([
            'objetivos.id',
            'objetivos.nombre as nombreObjetivo',
            'objetivos.tipo',
            'objetivos.idEje',
            'objetivos.abreviatura',
            'objetivos.priorizacion',
            'objetivos.priorizacionInterna',
            'objetivos.estado',
            DB::raw('0 as puntajes_indicadores'),
            DB::raw('0 as puntajes_indicadores_personalizado')
        ])
            ->whereHas('eje', function ($query) use ($idAsignatura) {
                $query->where('idAsignatura', $idAsignatura);
            })
            ->where('objetivos.estado', 'Activo');

        $objetivosPersonalizados = ObjetivoPersonalizado::with(['eje:id,nombre'])
        ->select([
            'objetivos_personalizados.id',
            'objetivos_personalizados.nombre as nombreObjetivo',
            'objetivos_personalizados.tipo',
            'objetivos_personalizados.idEje',
            'objetivos_personalizados.abreviatura',
            'objetivos_personalizados.priorizacion as priorizacionInterna',
            'objetivos_personalizados.estado',
            DB::raw('0 as puntajes_indicadores'),
            DB::raw('0 as puntajes_indicadores_personalizado'),
            DB::raw('NULL as priorizacion')
        ])
            ->whereHas('eje', function ($query) use ($idAsignatura) {
                $query->where('idAsignatura', $idAsignatura);
            })
            ->where('objetivos_personalizados.estado', 'Activo');

        // ✅ 3️⃣ Unir ambas consultas con `union()`
        $resultado = $objetivos->union($objetivosPersonalizados)
        ->orderBy('abreviatura')
        ->get();

        return response()->json($resultado);
    }

    // public function getObjetivosActivosAsignaturaEstablecimiento($idEstablecimientoActivo, $idAsignatura)
    // {
    //     $sql_oobjetivos =
    //         'SELECT
    //             ob.id
    //             , ob.nombre as nombreObjetivo
    //             , ej.nombre as nombreEje
    //             , ob.abreviatura
    //             , ob.priorizacion
    //             , ob.priorizacionInterna
    //             , ob.estado
    //             , ob.idEje
    //         FROM ejes as ej
    //         LEFT JOIN objetivos as ob
    //             ON ob.idEje = ej.id
    //         WHERE
    //             ej.idAsignatura = ' . $idAsignatura . ' AND
    //             ob.estado = "Activo"
    //         Order By ob.abreviatura
    //         ';

    //     $objetivos = DB::select($sql_oobjetivos, []);

    //     $sql_oobjetivos_personalizados =
    //         'SELECT
    //             ob.id
    //             , ob.nombre as nombreObjetivo
    //             , ej.nombre as nombreEje
    //             , ob.abreviatura
    //             , ob.priorizacion as priorizacionInterna
    //             , ob.idEstablecimiento
    //             , ob.estado
    //             , ob.idEje
    //         FROM ejes as ej
    //         LEFT JOIN objetivos_personalizados as ob
    //             ON ob.idEje = ej.id
    //         WHERE
    //             ej.idAsignatura = ' . $idAsignatura . ' AND
    //             ob.idEstablecimiento = ' . $idEstablecimientoActivo . ' AND
    //             ob.estado = "Activo"
    //         Order By ob.abreviatura
    //         ';

    //     $objetivos_personalizados = DB::select($sql_oobjetivos_personalizados, []);

    //     foreach ($objetivos as $key => $objetivo) {
    //         $objetivo->puntajes_indicadores = 0;
    //         $objetivo->puntajes_indicadores_personalizado = 0;
    //         $objetivo->tipo = 'Ministerio';
    //     }
    //     foreach ($objetivos_personalizados as $key => $objetivo_personalizado) {
    //         $objetivo_personalizado->puntajes_indicadores = 0;
    //         $objetivo_personalizado->puntajes_indicadores_personalizado = 0;
    //         $objetivo_personalizado->priorizacion = null;
    //         $objetivo_personalizado->tipo = 'Interno';
    //         array_push($objetivos, $objetivo_personalizado);
    //     }

    //     return $objetivos;
    // }

    /**
     * Obtiene objetivos por asignatura con
     * * $idAsignatura
     * * $tipoObjetivo
     * @return \Illuminate\Http\Response
     */
    // public function objetivosTrabajados(Request $request)
    // {
    //     try {
    //         $user = $request->user()->getUserData();
    //         $idPeriodo = $user['periodo']['id'];
    //         $idAsignatura = $request->idAsignatura;
    //         $idCurso = $request->idCurso;
    //         $objetivos = $request->objetivos; // Convertir a colección para manipularlo mejor


    //         // 1️⃣ Obtener todos los puntajes en una sola consulta para mejorar rendimiento
    //         $trabajadosNormales = Objetivo::getObjetivosTrabajados($objetivos, $idAsignatura, $idPeriodo, $idCurso);
    //         $trabajadosPersonalizados = Objetivo::getObjetivosTrabajadosPersonalizados($objetivos, $idAsignatura, $idPeriodo, $idCurso);

    //         // Asegurar que $objetivos sea una colección
    //         $objetivos = collect($objetivos);

    //         // 2️⃣ Mapear y calcular los puntajes
    //         $objetivosTrabajados = $objetivos->map(function ($objetivo) use ($trabajadosNormales, $trabajadosPersonalizados) {
    //             $puntajesNormales = $trabajadosNormales->where('idObjetivo', $objetivo)->sum('puntajes_indicadores');
    //             $puntajesPersonalizados = $trabajadosPersonalizados->where('idObjetivo', $objetivo)->sum('puntajes_indicadores');

    //             // Si al menos un puntaje es mayor a 0, se considera trabajado
    //             if ($puntajesNormales > 0 || $puntajesPersonalizados > 0) {
    //                 return [
    //                     'id' => $objetivo,
    //                     'puntajes_indicadores' => $puntajesNormales,
    //                     'puntajes_indicadores_personalizado' => $puntajesPersonalizados,
    //                 ];
    //             }

    //             return null;
    //         })->filter()->values(); // Eliminar nulos y reindexar colección

    //         return response()->json($objetivosTrabajados);
    //     } catch (\Exception $e) {
    //         logger()->error('Oas trabajados: ' . $e->getMessage());
    //         return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    //     }
    // }

    public function objetivosTrabajados(Request $request)
    {
        try {
            $user = $request->user()->getUserData();
            $idPeriodo = $user['periodo']['id'];
            $idAsignatura = $request->idAsignatura;
            $idCurso = $request->idCurso;
            $objetivos = collect($request->objetivos);

            // 🔹 Obtener objetivos trabajados separados por tipo
            $trabajadosNormales = Objetivo::getObjetivosTrabajados($objetivos, $idAsignatura, $idPeriodo, $idCurso);
            $trabajadosPersonalizados = Objetivo::getObjetivosTrabajadosPersonalizados($objetivos, $idAsignatura, $idPeriodo, $idCurso);

            // 🔹 Unir ambos conjuntos en una colección
            $objetivosTrabajados = $trabajadosNormales->merge($trabajadosPersonalizados)
                ->unique(function ($item) {
                    return $item['id'] . '-' . $item['tipoObjetivo']; // 🔹 Mantener ID + Tipo como clave única
                })
                ->values(); // 🔹 Reindexar la colección

            return response()->json($objetivosTrabajados);
        } catch (\Exception $e) {
            logger()->error('Error en objetivos trabajados: ' . $e->getMessage());
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
        // $user = $request->user()->getUserData();
        // $user['periodo']['id']
        $objetivos = Objetivo::getObjetivosMinisterio();
        $objetivos_personalizados = ObjetivoPersonalizado::getObjetivosPersonalizados();
        return $objetivos->merge($objetivos_personalizados);
    }

    /**
     * Obtiene objetivos por asignatura con
     * * $idAsignatura
     * @return \Illuminate\Http\Response
     */
    // public function getObjetivosBetwen($idCursoInicio, $idCursoFin)
    // {
    //     return Objetivo::getObjetivosBetwen($idCursoInicio, $idCursoFin);
    // }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storePersonalizado(Request $request)
    {
        try {
            // Validar los campos requeridos
            $request->validate([
                'nombre' => 'required|string',
                'abreviatura' => 'required|string',
                'idEje' => 'required|integer',
            ]);

            DB::transaction(function () use ($request) {
                
                $objetivo_personalizado = ObjetivoPersonalizado::Create([
                    'nombre'            => $request['nombre'],
                    'tipo'              => 'Interno',
                    'abreviatura'       => $request['abreviatura'],
                    'priorizacion'      => $request['priorizacion'],
                    'idEje'             => $request['idEje'],
                    'estado'            => $request['estado'],
                ]);

                $user = $request->user()->getUserData();
                foreach ($request['indicadores'] as $key => $indicador) {
                    IndicadoresPersonalizados::Create([
                        'nombre'        => $indicador['nombre'],
                        'tipo'          => 'Interno',
                        'idObjetivo'    => $objetivo_personalizado->id,
                        'tipo_objetivo' => 'Interno',
                        'idPeriodo'     => $user['periodo']['id'],
                        'idUsuario_created' => $user['id'],
                        'estado'        => 'Activo',
                    ]);
                }

                return response(null, 200);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Capturar la excepción de validación y devolver los errores
            return response()->json([
                'errors' => $e->errors()
            ], 422);
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
    // public function updatePersonalizado(Request $request, $id)
    // {
    //     try {

    //         $objetivo_personalizado = ObjetivoPersonalizado::findOrFail($id);
    //         $objetivo_personalizado->nombre = $request['nombre'];
    //         $objetivo_personalizado->abreviatura = $request['abreviatura'];
    //         $objetivo_personalizado->priorizacion = $request['priorizacion'];
    //         $objetivo_personalizado->idEje = $request['idEje'];
    //         $objetivo_personalizado->idEstablecimiento = $request['idEstablecimiento'];
    //         $objetivo_personalizado->estado = $request['estado'];
    //         $objetivo_personalizado->save();

    //         $indicadores = IndicadoresPersonalizados::select('id as idIndicador', 'nombre')
    //             ->where('idObjetivo', $id)->get();
    //         // Pasa a array el get
    //         $indicadores_eliminar = array();
    //         foreach ($indicadores as $key => $indicador) {
    //             array_push($indicadores_eliminar, $indicador->idIndicador);
    //         }

    //         foreach ($request['indicadores'] as $key => $indicador) {
    //             if (isset($indicador['idIndicador'])) {
    //                 $indicador_personalizado = IndicadoresPersonalizados::findOrFail($indicador['idIndicador']);
    //                 $indicador_personalizado->nombre = $indicador['nombre'];
    //                 $indicador_personalizado->save();
    //                 if (($key = array_search($indicador['idIndicador'], $indicadores_eliminar)) !== false) {
    //                     unset($indicadores_eliminar[$key]);
    //                 }
    //             } else {
    //                 IndicadoresPersonalizados::Create([
    //                     'nombre'     => $indicador['nombre'],
    //                     'idObjetivo' => $id,
    //                     'estado'     => 'Activo',
    //                 ]);
    //             }
    //         }

    //         foreach ($indicadores_eliminar as $key => $idIndicador) {
    //             $indicador_eliminar = IndicadoresPersonalizados::findOrFail($idIndicador);
    //             $indicador_eliminar->estado = 'Inactivo';
    //             $indicador_eliminar->save();
    //         }

    //         return response('success', 200);
    //     } catch (\Throwable $th) {
    //         return response($th, 500);
    //     }
    // }

    /**
     * update estado OA MINISTERIO
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
     * update estado OA personalizado (interno)
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
     * update Priorización OA personalizado (interno)
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
     * update Priorización OA MINISTERIO
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updatePriorizacioMinisterio(Request $request, $id)
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
