<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Evaluacion;
use Illuminate\Support\Facades\DB;

class SyncLibroController extends Controller
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

            // * 1 Obtener datos de conexión del establecimiento
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

            $evaluacionesIds = $request->input('evaluaciones_ids');

            // * 2 Obtener datos completos de las evaluaciones
            $datosEvaluaciones = $this->obtenerDatosEvaluaciones($evaluacionesIds);

            // * 3 Procesar y formatear los datos para sincronización
            $datosProcesados = $this->procesarDatosParaSincronizacion(
                $datosEvaluaciones,
                $establecimiento
            );

            // * 4 Envia datos a Libro Digital
            $resultadoSincronizacion = $this->enviarDatosASistemaExterno(
                $datosProcesados,
                $establecimiento
            );
            // Verificar si hay un error en la sincronización
            if (isset($resultadoSincronizacion['status']) && $resultadoSincronizacion['status'] === 'Error') {
                return response()->json([
                    'status' => 'error',
                    'message' => $resultadoSincronizacion['message'] ?? 'Error en la sincronización',
                    'details' => $resultadoSincronizacion
                ], 400);
            }

            // * 5 Actualizar estado de sincronización de las evaluaciones
            $this->actualizarEvaluaciones($resultadoSincronizacion);

            return response()->json([
                'status' => 'success',
                'message' => 'Evaluaciones',
                'resultadoSincronizacion' => $resultadoSincronizacion
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación en los datos proporcionados',
                'errors' => $e->errors(),
                'code' => 'VALIDATION_ERROR'
            ], 422);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de conexión con el sistema externo: ' . $e->getMessage(),
                'code' => 'CONNECTION_ERROR',
                'details' => [
                    'request' => $e->getRequest() ? (string)$e->getRequest()->getUri() : null,
                    'response' => $e->getResponse() ? $e->getResponse()->getStatusCode() : null
                ]
            ], 503);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al sincronizar evaluaciones: ' . $e->getMessage(),
                'code' => 'INTERNAL_ERROR',
                'details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
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
            ->select('id', 'nombre', 'fecha', 'estado_sync', 'id_evaluacion_ld', 'idCurso', 'idAsignatura', 'idEstabUsuarioRol', 'idSubperiodo')
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
                'estado_sync' => $evaluacion->estado_sync,
                'id_evaluacion_ld' => $evaluacion->id_evaluacion_ld,
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
     * Sincroniza las evaluaciones de todos los establecimientos definidos
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function sincronizarEvaluacionesMultiples()
    {
        // Tiempo de inicio
        $tiempoInicio = microtime(true);

        // Definir los usuarios para el login
        $usuarios = [
            // ['correo' => '6.director@dev.cl', 'password' => '6.123456'],
            ['correo' => '25.director@dev.cl', 'password' => '25.123456'],
            ['correo' => '26.director@dev.cl', 'password' => '26.123456'],
            ['correo' => '27.director@dev.cl', 'password' => '27.123456'],
            ['correo' => '28.director@dev.cl', 'password' => '28.123456'],
            ['correo' => '29.director@dev.cl', 'password' => '29.123456'],
            ['correo' => '30.director@dev.cl', 'password' => '30.123456'],
            ['correo' => '31.director@dev.cl', 'password' => '31.123456'],
            ['correo' => '33.director@dev.cl', 'password' => '33.123456'],
            ['correo' => '34.director@dev.cl', 'password' => '34.123456'],
            ['correo' => '35.director@dev.cl', 'password' => '35.123456'],
            ['correo' => '36.director@dev.cl', 'password' => '36.123456'],
        ];

        $resultados = [];

        try {
            logger()->info(['--- INICIO DE SINCRONIZACION ---']);
            // Procesar cada usuario (establecimiento)
            foreach ($usuarios as $index => $usuario) {
                logger()->info(['Iniciando sincronización para el usuario:' => $usuario['correo']]);

                // Realizar login con AuthController
                $authController = new \App\Http\Controllers\Auth\AuthController();
                $request = new \Illuminate\Http\Request();
                $request->replace($usuario);

                $respuestaLogin = $authController->login($request);
                $contenidoRespuesta = json_decode($respuestaLogin->getContent(), true);

                // Verificar si el login fue exitoso
                if (!isset($contenidoRespuesta['token'])) {
                    logger()->error(['Error al iniciar sesión con el usuario:' => $usuario['correo']], $contenidoRespuesta);
                    $resultados[] = [
                        'usuario' => $usuario['correo'],
                        'estado' => 'error',
                        'mensaje' => 'No se pudo iniciar sesión',
                        'detalles' => $contenidoRespuesta
                    ];
                    continue;
                }
                // Buscar evaluaciones pendientes de sincronización
                $evaluacionesPendientes = Evaluacion::where('estado_sync', '!=', 'sync')
                    ->where('estado', 'Activo')
                    ->pluck('id')
                    ->toArray();

                if (empty($evaluacionesPendientes)) {
                    logger()->info(['Todas las evaluaciones están sincronizadas para: ' => $usuario['correo']]);
                    $resultados[] = [
                        'usuario' => $usuario['correo'],
                        'estado' => 'success',
                        'mensaje' => 'No hay evaluaciones pendientes',
                        'evaluaciones_procesadas' => 0
                    ];
                    continue;
                }

                // Preparar la petición para sincronizarEvaluaciones
                $requestSync = new \Illuminate\Http\Request();
                $requestSync->replace(['evaluaciones_ids' => $evaluacionesPendientes]);

                // Establecer el token en la petición
                $requestSync->headers->set('Authorization', 'Bearer ' . $contenidoRespuesta['token']);

                // Mantener el usuario actual en la petición
                $requestSync->setUserResolver(function () use ($contenidoRespuesta) {
                    return new class($contenidoRespuesta['user'], $contenidoRespuesta['roles']) {
                        private $user;
                        private $roles;

                        public function __construct($user, $roles)
                        {
                            $this->user = $user;
                            $this->roles = $roles;
                        }

                        public function getUserData()
                        {
                            return [
                                'id' => $this->user['id'],
                                'establecimiento' => [
                                    'id' => $this->roles['id_estab']
                                ],
                                'periodo' => [
                                    'id' => 1 // Valor predeterminado si no está disponible
                                ]
                            ];
                        }
                    };
                });

                // Llamar a la función de sincronización
                $respuestaSinc = $this->sincronizarEvaluaciones($requestSync);
                $resultadoSinc = json_decode($respuestaSinc->getContent(), true);

                logger()->info(['Resultado de sincronización para' => $usuario['correo']], [
                    'status' => $resultadoSinc['status'] ?? 'error',
                    'mensaje' => $resultadoSinc['message'] ?? 'Sin mensaje'
                ]);

                $resultados[] = [
                    'usuario' => $usuario['correo'],
                    'estado' => $resultadoSinc['status'] ?? 'error',
                    'mensaje' => $resultadoSinc['message'] ?? 'Error en la sincronización',
                    'evaluaciones_procesadas' => count($evaluacionesPendientes),
                    'detalles' => $resultadoSinc
                ];

                // Cerrar sesión para el usuario actual
                $authController->logout($requestSync);
            }

            // Calcular tiempo total
            $tiempoFin = microtime(true);
            $tiempoTotal = round($tiempoFin - $tiempoInicio, 2);

            logger()->info(['--- Sincronización completada en' => $tiempoTotal . ' segundos ---']);

            return response()->json([
                'status' => 'success',
                'message' => 'Proceso de sincronización completado',
                'tiempo_total' => $tiempoTotal,
                'resultados' => $resultados
            ]);
        } catch (\Exception $e) {
            logger()->error(['Error en la sincronización múltiple:' => $e->getMessage()], [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $tiempoFin = microtime(true);
            $tiempoTotal = round($tiempoFin - $tiempoInicio, 2);

            return response()->json([
                'status' => 'error',
                'message' => 'Error en el proceso de sincronización múltiple',
                'tiempo_total' => $tiempoTotal,
                'error' => $e->getMessage(),
                'resultados' => $resultados
            ], 500);
        }
    }
}
