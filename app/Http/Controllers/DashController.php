<?php

namespace App\Http\Controllers;

use App\Models\dash_ld_conexion;
use App\Models\dash_ld_conexion_log;
use App\Models\Evaluacion;
use App\Models\Notas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashController extends Controller
{

    /**
     * @OA\Post(
     *     path="/sincronizar-evaluaciones",
     *     tags={"Sincronización"},
     *     summary="Sincronizar evaluaciones con Libro Digital",
     *     description="Sincroniza las evaluaciones seleccionadas con el sistema de Libro Digital",
     *     operationId="sincronizarEvaluaciones",
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa"
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="evaluaciones_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     )
     * )
     */
    public function sincronizarEvaluaciones(Request $request)
    {
        try {
            // Validar datos de entrada
            $request->validate([
                'evaluaciones_ids' => 'required|array',
                'evaluaciones_ids.*' => 'integer|exists:evaluaciones,id'
            ]);

            $user = $request->user()->getUserData();
            $idEstablecimiento = $user['establecimiento']['id'];

            // Obtener datos de conexión del establecimiento
            $establecimiento = DB::connection('master')
                ->table('establecimientos')
                ->where('id', $idEstablecimiento)
                ->select('rbd', 'link_ld', 'user_ld', 'pass_ld')
                ->first();

            if (
                !$establecimiento
                || !$establecimiento->link_ld
                || !$establecimiento->user_ld
                || !$establecimiento->pass_ld
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se encontraron datos de conexión para el establecimiento'
                ], 400);
            }

            // Obtener los IDs de evaluaciones
            $evaluacionesIds = $request->input('evaluaciones_ids');

            // Obtener datos completos de las evaluaciones
            $datosEvaluaciones = $this->obtenerDatosEvaluaciones($evaluacionesIds);

            // Procesar y formatear los datos para sincronización
            $datosProcesados = $this->procesarDatosParaSincronizacion(
                $datosEvaluaciones,
                $establecimiento
            );

            // Envia datos a Libro Digital
            $resultadoSincronizacion = $this->enviarDatosASistemaExterno(
                $datosProcesados,
                $establecimiento
            );

            $this->actualizarEvaluaciones($resultadoSincronizacion);

            return response()->json([
                'status' => 'success',
                'message' => 'Evaluaciones',
                'resultadoSincronizacion' => $resultadoSincronizacion
            ]);
            // Registrar resultado de la sincronización en logs
            // $this->registrarResultadoSincronizacion(
            //     $evaluacionesIds,
            //     $resultadoSincronizacion
            // );

            return response()->json([
                'status' => 'success',
                'message' => 'Evaluaciones sincronizadas correctamente',
                'data' => $resultadoSincronizacion
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al sincronizar evaluaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    public function actualizarEvaluaciones($resultadoSincronizacion)
    {
        $fechaActual = date('Y-m-d');
        $evaluaciones = $resultadoSincronizacion['resultados'];
        foreach ($evaluaciones['exitosas'] as $evaluacion) {
            Evaluacion::where('id', $evaluacion['id_evaluacion_bru'])->update([
                'id_evaluacion_ld' => $evaluacion['id_evaluacion_ld'],
                'estado_sync' => 'sync',
                'fecha_sync' => $fechaActual,
                'log' => null,
            ]);
        }
        foreach ($evaluaciones['fallidas'] as $evaluacion) {
            $log = [
                'error' => $evaluacion['error'],
                'errores' => isset($evaluacion['errores']) ? $evaluacion['errores'] : null
            ];
            Evaluacion::where('id', $evaluacion['id_evaluacion_bru'])->update([
                'log' => json_encode($log),
                'estado_sync' => 'error',
                'fecha_sync' => $fechaActual
            ]);
        }
    }

    /**
     * Obtiene los datos completos de las evaluaciones desde la base de datos
     * 
     * @param array $evaluacionesIds IDs de las evaluaciones a consultar
     * @return \Illuminate\Database\Eloquent\Collection Colección de evaluaciones con sus relaciones
     */
    private function obtenerDatosEvaluaciones(array $evaluacionesIds)
    {
        return Evaluacion::with([
            'curso' => function ($query) {
                $query->where('estado', 'Activo')
                    ->select('id', 'nombre', 'letra', 'idGrado');
            },
            'curso.grado' => function ($query) {
                $query->select('id', 'idGrado', 'nombre', 'idNivel');
            },
            'estabUsuarioRol' => function ($query) {
                $query->select('id', 'idUsuario');
            },
            'estabUsuarioRol.usuario' => function ($query) {
                $query->select('id', 'rut');
            },
            'asignatura' => function ($query) {
                $query->where('estado', 'Activo')
                    ->select('id', 'nombre');
            },
            'evaluacionesNotas' => function ($query) {
                $query->select('id', 'nota', 'idAlumno', 'idEvaluacion');
            },
            'evaluacionesNotas.alumno' => function ($query) {
                $query->select('id', 'rut');
            },
            'subperiodo' => function ($query) {
                $query->select('id', 'nombre', 'idAjuste');
            },
            'subperiodo.ajuste' => function ($query) {
                $query->select('id', 'idPeriodo');
            },
            'subperiodo.ajuste.periodo' => function ($query) {
                $query->select('id', 'nombre');
            },
        ])
            ->whereIn('id', $evaluacionesIds)
            ->where('estado', 'Activo')
            ->where('estado_sync', '!=', 'sync')
            ->select('id', 'nombre', 'fecha', 'idCurso', 'idAsignatura', 'idEstabUsuarioRol', 'idSubperiodo')
            ->get();
    }

    /**
     * Procesa y formatea los datos de evaluaciones para la sincronización
     * 
     * @param \Illuminate\Database\Eloquent\Collection $evaluaciones Colección de evaluaciones
     * @return array Datos procesados listos para sincronización
     */
    private function procesarDatosParaSincronizacion($evaluaciones, $establecimiento)
    {
        $datosProcesados = [];
        $datosProcesados['rbd'] = $establecimiento->rbd;
        $periodo = null;
        $subperiodo = null;
        $curso = null;
        foreach ($evaluaciones as $evaluacion) {

            if ($subperiodo == null) {
                $subperiodo = isset($evaluacion->subperiodo) ?
                    $evaluacion->subperiodo->nombre : null;
            }

            if ($periodo == null) {
                $periodo = isset($evaluacion->subperiodo->ajuste->periodo) ?
                    $evaluacion->subperiodo->ajuste->periodo->nombre : null;
            }

            if ($curso == null) {
                $curso = isset($evaluacion->curso) ? [
                    'nombre' => $evaluacion->curso->nombre,
                    'letra' => $evaluacion->curso->letra,
                    'grado' => isset($evaluacion->curso->grado) ? [
                        'idGrado' => $evaluacion->curso->grado->idGrado,
                        'nombre' => $evaluacion->curso->grado->nombre,
                        'idNivel' => $evaluacion->curso->grado->idNivel
                    ] : null
                ] : null;
            }

            $notasFormateadas = [];
            foreach ($evaluacion->evaluacionesNotas as $evaluacionNota) {
                if (isset($evaluacionNota->alumno)) {
                    $notasFormateadas[] = [
                        'rut_alumno' => $evaluacionNota->alumno->rut,
                        'nota' => $evaluacionNota->nota
                    ];
                }
            }

            $datosProcesados['evaluaciones'][] = [
                'id_evaluacion' => $evaluacion->id,
                'nombre_evaluacion' => $evaluacion->nombre,
                'fecha' => $evaluacion->fecha,
                'rut_docente' => isset($evaluacion->estabUsuarioRol->usuario) ?
                    $evaluacion->estabUsuarioRol->usuario->rut : null,
                'asignatura' => isset($evaluacion->asignatura) ? $evaluacion->asignatura->nombre : null,
                'notas' => $notasFormateadas
            ];
        }

        $datosProcesados['periodo'] = $periodo;
        $datosProcesados['subperiodo'] = $subperiodo;
        $datosProcesados['curso'] = $curso;
        return $datosProcesados;
    }


    /**
     * Envía los datos procesados al sistema externo
     * 
     * @param array $datosProcesados Datos formateados para sincronización
     * @return array Resultado de la sincronización
     */
    private function enviarDatosASistemaExterno(array $datosProcesados, $establecimiento)
    {
        try {
            // Primero obtener el token de autenticación
            $client = new \GuzzleHttp\Client();

            // Hacer login para obtener el token (si es necesario)
            $loginResponse = $client->post($establecimiento->link_ld . '/login', [
                'json' => [
                    'rut' => $establecimiento->user_ld,
                    'password' => '12345'
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);

            $loginData = json_decode($loginResponse->getBody()->getContents(), true);
            $token = $loginData['access_token'] ?? $loginData['token'] ?? null;

            if (!$token) {
                throw new \Exception('No se pudo obtener el token de autenticación');
            }

            // Ahora enviar los datos con el token
            $response = $client->post($establecimiento->link_ld . '/sincronizar-evaluaciones', [
                'json' => $datosProcesados,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error en sincronización: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Registra el resultado de la sincronización en la base de datos
     * 
     * @param array $evaluacionesIds IDs de las evaluaciones sincronizadas
     * @param array $resultadoSincronizacion Resultado de la sincronización
     * @return void
     */
    private function registrarResultadoSincronizacion(array $evaluacionesIds, array $resultadoSincronizacion)
    {
        // Aquí implementaríamos la lógica para registrar el resultado en la base de datos
        // Por ejemplo, actualizar el estado de sincronización de las evaluaciones

        foreach ($resultadoSincronizacion as $resultado) {
            // Actualizar estado de sincronización en la base de datos
            // \App\Models\Evaluacion::where('id', $resultado['id_evaluacion'])
            //     ->update(['estado_sincronizacion' => $resultado['estado']]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getLdConexions(Request $request)
    {
        try {
            $user = $request->user()->getUserData();
            $idPeriodo = $user['periodo']['id'];

            // Optimización: Carga las conexiones con sus logs en una sola consulta usando eager loading
            $dash_ld_conexion = dash_ld_conexion::with(['logs' => function ($query) {
                $query->select('id', 'state', 'message', 'nombreCurso', 'idLdConexion');
            }])
            ->where('idPeriodo', $idPeriodo)
                ->orderBy('created_at', 'desc')
            ->get();

            return response()->json([
                'status' => 'success',
                'data' => $dash_ld_conexion
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener las conexiones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addLdConexion(Request $request)
    {
        try {

            DB::transaction(function () use ($request) {
                $user = $request->user()->getUserData();
                $idPeriodo = $user['periodo']['id'];
                $idUsuario = $user['id'];
                $dash_ld_conexion = dash_ld_conexion::Create([
                    'idPeriodo'         => $idPeriodo,
                    'idUsuario'         => $idUsuario,
                ]);

                foreach ($request['logs'] as $key => $log) {
                    dash_ld_conexion_log::Create([
                        'state'         => $log['state'],
                        'message'       => $log['message'],
                        'nombreCurso'   => $log['nombreCurso'],
                        'idLdConexion' => $dash_ld_conexion->id,
                    ]);
                }
            });
            return response()->json(['status' => 'success', 'message' => 'Logs guardados con éxito!.']);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error al procesar la solicitud.',
                'error' => [
                    'type' => get_class($th),
                    'message' => $th->getMessage(),
                    'file' => $th->getFile(),
                    'line' => $th->getLine(),
                    'trace' => $th->getTraceAsString()
                ]
            ]);
        }
    }

    public function getAllNotas(Request $request)
    {
        try {
            $user = $request->user()->getUserData();
            $idPeriodo = $user['periodo']['id'];
            $cursos = $request['cursos'];
            $response = [];

            foreach ($cursos as $index => $curso) {
                // Consulta principal en la base de datos del establecimiento (tenant)
                $notasCurso = Notas::select(
                    'notas.*',
                    'alumnos.tipoDocumento',
                    'alumnos.rut as rutAlumno',
                    'alumnos.nombres as nombreAlumno',
                    'alumnos.primerApellido',
                    'alumnos.segundoApellido',
                    'cursos.letra',
                    'cursos.idGrado',
                )
                    ->leftJoin("alumnos", "alumnos.id", "=", "notas.idAlumno")
                    ->leftJoin("cursos", "cursos.id", "=", "notas.idCurso")
                    ->where('notas.idPeriodo', $idPeriodo)
                    ->where('notas.idCurso', $curso['id'])
                    ->where('cursos.estado', 'Activo')
                    ->get();

                // Enriquecemos los resultados con datos de la base master
                $notasCurso = $notasCurso->map(function ($nota) {
                    // Obtenemos datos de la base master
                    $periodoInfo = DB::connection('master')
                        ->table('periodos')
                        ->where('id', $nota->idPeriodo)
                        ->first();

                    $gradoInfo = DB::connection('master')
                        ->table('grados')
                        ->where('id', $nota->idGrado)
                        ->first();

                    $asignaturaInfo = DB::connection('master')
                        ->table('asignaturas')
                        ->where('id', $nota->idAsignatura)
                        ->where('estado', 'Activo')
                        ->first();

                    // Agregamos la información de master al resultado
                    $nota->nombrePeriodo = $periodoInfo->nombre ?? null;
                    $nota->idGrado = $gradoInfo->idGrado ?? null;
                    $nota->nivelGrado = $gradoInfo->idNivel ?? null;
                    $nota->nombreGrado = $gradoInfo->nombre ?? null;
                    $nota->nombreAsignatura = $asignaturaInfo->nombre ?? null;

                    return $nota;
                });

                $response[$index]['idCurso'] = $curso['id'];
                $response[$index]['notas'] = $notasCurso;
            }

            return $response;
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error al procesar la solicitud.',
                'error' => [
                    'type' => get_class($th),
                    'message' => $th->getMessage(),
                    'file' => $th->getFile(),
                    'line' => $th->getLine(),
                    'trace' => $th->getTraceAsString()
                ]
            ]);
        }
    }
}
