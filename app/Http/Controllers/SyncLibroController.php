<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use Illuminate\Http\Request;

use App\Models\Evaluacion;
use App\Models\Master\Asignatura;
use App\Models\UsuarioAsignatura;
use Illuminate\Support\Facades\DB;

class SyncLibroController extends Controller
{

    // Definir la propiedad protegida para los usuarios

    // * DEV
    protected $usuarios = [
        ['correo' => '6.director@dev.cl', 'password' => '6.123456'],
    ];

    // * PROD
    // protected $usuarios = [
    //     ['correo' => '25.director@dev.cl', 'password' => '25.123456'],
    //     ['correo' => '26.director@dev.cl', 'password' => '26.123456'],
    //     ['correo' => '27.director@dev.cl', 'password' => '27.123456'],
    //     ['correo' => '28.director@dev.cl', 'password' => '28.123456'],
    //     ['correo' => '29.director@dev.cl', 'password' => '29.123456'],
    //     ['correo' => '30.director@dev.cl', 'password' => '30.123456'],
    //     ['correo' => '31.director@dev.cl', 'password' => '31.123456'],
    //     ['correo' => '34.director@dev.cl', 'password' => '34.123456'],
    //     ['correo' => '35.director@dev.cl', 'password' => '35.123456'],
    //     ['correo' => '36.director@dev.cl', 'password' => '36.123456'],
    //     // ['correo' => '33.director@dev.cl', 'password' => '33.123456'], // el rincón
    // ];

    protected $password = '12345'; // * DEV
    // protected $password = '123456'; // * PROD

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
                logger()->error(['No se encontraron datos de conexión para el establecimiento.']);
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
            if (
                is_array($resultadoSincronizacion) &&
                isset($resultadoSincronizacion['status']) &&
                $resultadoSincronizacion['status'] === 'Error'
            ) {
                logger()->error('Error detectado en la respuesta de sincronización', [
                    'resultadoSincronizacion' => $resultadoSincronizacion
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => $resultadoSincronizacion['message'] ?? 'Error en la sincronización',
                    'details' => $resultadoSincronizacion
                ], 400);
            }

            // Verificar que la estructura es válida para actualizarEvaluaciones
            if (!is_array($resultadoSincronizacion) || !isset($resultadoSincronizacion['resultados'])) {
                logger()->error('Estructura de respuesta inválida para actualizarEvaluaciones', [
                    'resultadoSincronizacion' => $resultadoSincronizacion
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'La respuesta del sistema externo no tiene el formato esperado',
                    'details' => $resultadoSincronizacion
                ], 400);
            }

            // * 5 Actualizar estado de sincronización de las evaluaciones
            $this->actualizarEvaluaciones($resultadoSincronizacion);
            return response()->json([
                'status' => 'success',
                'message' => 'Evaluaciones 1111',
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
        // Verificar que $resultadoSincronizacion sea un array y contenga la clave 'resultados'
        if (!is_array($resultadoSincronizacion) || !isset($resultadoSincronizacion['resultados'])) {
            // Registrar el error y salir
            logger()->error('Error en actualizarEvaluaciones: formato incorrecto de resultadoSincronizacion', [
                'resultadoSincronizacion' => $resultadoSincronizacion
            ]);
            return;
        }

        $fechaActual = date('Y-m-d');
        $evaluaciones = $resultadoSincronizacion['resultados'];

        // Verificar que existan las claves 'exitosas' y 'fallidas'
        if (!isset($evaluaciones['exitosas']) || !is_array($evaluaciones['exitosas'])) {
            $evaluaciones['exitosas'] = [];
        }

        if (!isset($evaluaciones['fallidas']) || !is_array($evaluaciones['fallidas'])) {
            $evaluaciones['fallidas'] = [];
        }

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
                'error' => $evaluacion['error'] ?? 'Error desconocido',
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
                $query->select('id', 'rut')
                    ->whereHas('curso', function ($q) {
                        $q->where('alumnos_cursos.estado', 'Activo');
                    });
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
        // Agrupar evaluaciones por curso
        $evaluacionesPorCurso = [];
        foreach ($evaluaciones as $evaluacion) {
            if (isset($evaluacion->curso)) {
                $cursoKey = $evaluacion->curso->id;
                if (!isset($evaluacionesPorCurso[$cursoKey])) {
                    $evaluacionesPorCurso[$cursoKey] = [
                        'curso' => [
                            'nombre' => $evaluacion->curso->nombre,
                            'letra' => $evaluacion->curso->letra,
                            'grado' => isset($evaluacion->curso->grado) ? [
                                'idGrado' => $evaluacion->curso->grado->idGrado,
                                'nombre' => $evaluacion->curso->grado->nombre,
                                'idNivel' => $evaluacion->curso->grado->idNivel
                            ] : null
                        ],
                        'evaluaciones' => []
                    ];
                }
                $evaluacionesPorCurso[$cursoKey]['evaluaciones'][] = $evaluacion;
            }
        }

        // Procesar cada grupo de evaluaciones por curso
        $resultados = [];
        foreach ($evaluacionesPorCurso as $cursoData) {
            $datosProcesados = [];
            $datosProcesados['rbd'] = $establecimiento->rbd;
            $datosProcesados['curso'] = $cursoData['curso'];

            // Obtener periodo y subperiodo de la primera evaluación del curso
            $primeraEvaluacion = $cursoData['evaluaciones'][0];
            $datosProcesados['periodo'] = isset($primeraEvaluacion->subperiodo->ajuste->periodo) ?
                $primeraEvaluacion->subperiodo->ajuste->periodo->nombre : null;
            $datosProcesados['subperiodo'] = isset($primeraEvaluacion->subperiodo) ?
                $primeraEvaluacion->subperiodo->nombre : null;

            // Procesar evaluaciones del curso
            foreach ($cursoData['evaluaciones'] as $evaluacion) {
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

            $resultados[] = $datosProcesados;
        }

        return $resultados;
    }

    /**
     * Envía los datos procesados al sistema externo
     * 
     * @param array $datosProcesados Datos formateados para sincronización
     * @return array Resultado de la sincronización
     */
    private function enviarDatosASistemaExterno(array $datosProcesados, $establecimiento)
    {
        $resultados = [
            'resultados' => [
                'exitosas' => [],
                'fallidas' => []
            ]
        ];

        foreach ($datosProcesados as $datosCurso) {
            try {
                // Primero obtener el token de autenticación
                $client = new \GuzzleHttp\Client();

                // Hacer login para obtener el token (si es necesario)
                $loginResponse = $client->post($establecimiento->link_ld . '/login', [
                    'json' => [
                        'rut' => $establecimiento->user_ld,
                        'password' => $this->password
                    ],
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ]
                ]);

                $loginData = json_decode($loginResponse->getBody()->getContents(), true);
                $token = $loginData['access_token'] ?? $loginData['token'] ?? null;

                if (!$token) {
                    logger()->error('Error en sincronización: No se pudo obtener el token de autenticación', [
                        'loginData' => $loginData
                    ]);
                    return [
                        'status' => 'Error',
                        'message' => 'Error en sincronización: No se pudo obtener el token de autenticación'
                    ];
                }

                // Ahora enviar los datos con el token
                $response = $client->post($establecimiento->link_ld . '/sincronizar-evaluaciones', [
                    'json' => $datosCurso,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);

                $responseContent = $response->getBody()->getContents();
                $resultadoCurso = json_decode($responseContent, true);

                if (isset($resultadoCurso['resultados'])) {
                    $resultados['resultados']['exitosas'] = array_merge(
                        $resultados['resultados']['exitosas'],
                        $resultadoCurso['resultados']['exitosas'] ?? []
                    );
                    $resultados['resultados']['fallidas'] = array_merge(
                        $resultados['resultados']['fallidas'],
                        $resultadoCurso['resultados']['fallidas'] ?? []
                    );
                }
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                logger()->error('Error de solicitud en sincronización', [
                    'message' => $e->getMessage(),
                    'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
                ]);

                return [
                    'status' => 'Error',
                    'message' => 'Error en solicitud de sincronización: ' . $e->getMessage(),
                    'response' => $e->hasResponse() ? json_decode($e->getResponse()->getBody()->getContents(), true) : null
                ];
            } catch (\Exception $e) {
                logger()->error('Excepción general en sincronización', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return [
                    'status' => 'Error',
                    'message' => 'Error general en sincronización: ' . $e->getMessage()
                ];
            }
        }

        return $resultados;
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

        $resultados = [];

        try {
            logger()->info(['--- INICIO DE MULTI SINCRONIZACION ---']);
            // Procesar cada usuario (establecimiento)
            foreach ($this->usuarios as $index => $usuario) {

                // Realizar login con AuthController
                $authController = new \App\Http\Controllers\Auth\AuthController();
                $request = new \Illuminate\Http\Request();
                $request->replace($usuario);

                $respuestaLogin = $authController->login($request);
                $contenidoRespuesta = json_decode($respuestaLogin->getContent(), true);
                $roles = $contenidoRespuesta['roles'];
                $nombreEstablecimiento = $roles['nombre_estab'];

                logger()->info([' > > > ' . $nombreEstablecimiento . ' < < <']);
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
                    logger()->info(['Todas las evaluaciones están en estado SYNC']);
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
                if ($respuestaSinc instanceof \Illuminate\Http\JsonResponse) {
                    $resultadoSinc = json_decode($respuestaSinc->getContent(), true);
                } else {
                    $resultadoSinc = $respuestaSinc;
                }

                $resultados[] = [
                    'estado' => $resultadoSinc['status'] ?? 'error',
                    'mensaje' => $resultadoSinc['message'] ?? 'Error en la sincronización',
                    'usuario' => $usuario['correo'],
                    'establecimiento' => $nombreEstablecimiento,
                    'evaluaciones_procesadas' => count($evaluacionesPendientes),
                    'detalles' => $resultadoSinc
                ];

                // Cerrar sesión para el usuario actual
                $authController->logout($requestSync);
            }

            // Calcular tiempo total
            $tiempoFin = microtime(true);
            $tiempoTotal = round($tiempoFin - $tiempoInicio, 2);

            logger()->info(['> Sincronización completada en' . $tiempoTotal . ' SEG, para: ' . $usuario['correo'] . '.']);
            logger()->info(['> > > - - - - - - - - - - - - - - - - - - - - - - - - - - - < < <']);

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


    public function cantidadEvaluacionesGeneral()
    {
        // Tiempo de inicio
        $tiempoInicio = microtime(true);

        $resultados = [];

        try {
            logger()->info(['--- INICIO DE CONTEO DE EVALUACIONES ---']);
            // Procesar cada usuario (establecimiento)
            foreach ($this->usuarios as $index => $usuario) {

                // Realizar login con AuthController
                $authController = new \App\Http\Controllers\Auth\AuthController();
                $request = new \Illuminate\Http\Request();
                $request->replace($usuario);

                $respuestaLogin = $authController->login($request);
                $contenidoRespuesta = json_decode($respuestaLogin->getContent(), true);
                $roles = $contenidoRespuesta['roles'];
                $nombreEstablecimiento = $roles['nombre_estab'];

                logger()->info([' > > > ' . $nombreEstablecimiento . ' < < <']);
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
                $evaluaciones = Evaluacion::where('estado_sync', '!=', 'sync')
                    ->where('estado', 'Activo')
                    ->pluck('id')
                    ->toArray();

                $conteosPorEstado = Evaluacion::select('estado_sync', DB::raw('count(*) as total'))
                    ->groupBy('estado_sync')
                    ->pluck('total', 'estado_sync')
                    ->toArray();

                $totalEvaluaciones = array_sum($conteosPorEstado);

                $resultados[] = [
                    'Establecimiento' => $nombreEstablecimiento,
                    'Total' => $totalEvaluaciones,
                    'Sincronizadas' => $conteosPorEstado['sync'] ?? 0,
                    'Pendientes' => ($conteosPorEstado['de_sync'] ?? 0) + ($conteosPorEstado['no_sync'] ?? 0),
                    'Fallidas' => $conteosPorEstado['error'] ?? 0,
                ];
            }

            return response()->json($resultados);
        } catch (\Exception $e) {
            logger()->error(['Error en el conteo de evaluaciones:' => $e->getMessage()], [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error en el proceso de conteo de evaluaciones',
                'error' => $e->getMessage(),
                'resultados' => $resultados
            ], 500);
        }
    }


    public function asignaturasBrujula()
    {
        // Tiempo de inicio
        $tiempoInicio = microtime(true);

        $resultados = [];

        try {
            logger()->info(['--- INICIO DE CONSULTA DE ASIGNATURAS ---']);
            // Procesar cada usuario (establecimiento)
            foreach ($this->usuarios as $index => $usuario) {

                // Realizar login con AuthController
                $authController = new \App\Http\Controllers\Auth\AuthController();
                $request = new \Illuminate\Http\Request();
                $request->replace($usuario);

                $respuestaLogin = $authController->login($request);
                $contenidoRespuesta = json_decode($respuestaLogin->getContent(), true);
                $roles = $contenidoRespuesta['roles'];
                $nombreEstablecimiento = $roles['nombre_estab'];

                logger()->info([' > > > ' . $nombreEstablecimiento . ' < < <']);
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

                // Consultar UsuarioAsignatura con sus relaciones
                $usuarioAsignaturas = UsuarioAsignatura::with(['asignatura', 'curso'])
                    ->whereHas('curso', function ($query) {
                        $query->where('idGrado', '>', 5);
                    })
                    ->get();

                $asignaturasPorCurso = [];
                foreach ($usuarioAsignaturas as $ua) {
                    $cursoId = $ua->curso->id;
                    $cursoNombre = $ua->curso->nombre;
                    $asignaturaId = $ua->asignatura->id;
                    $asignaturaNombre = $ua->asignatura->nombre;

                    if (!isset($asignaturasPorCurso[$cursoId])) {
                        $asignaturasPorCurso[$cursoId] = [
                            'curso_nombre' => $cursoNombre,
                            'asignaturas' => []
                        ];
                    }

                    $asignaturasPorCurso[$cursoId]['asignaturas'][] = [
                        'nombre' => $asignaturaNombre
                    ];
                }

                $resultados[] = [
                    'Establecimiento' => $nombreEstablecimiento,
                    'Cursos' => array_values($asignaturasPorCurso)
                ];
            }

            return response()->json($resultados);
        } catch (\Exception $e) {
            logger()->error(['Error en la consulta de asignaturas:' => $e->getMessage()], [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error en el proceso de consulta de asignaturas',
                'error' => $e->getMessage(),
                'resultados' => $resultados
            ], 500);
        }
    }

    // * COMPARATIVA ASIGNATURAS
    public function comparativaAsignaturas()
    {
        // Tiempo de inicio
        $tiempoInicio = microtime(true);

        $resultados = [];

        try {
            logger()->info(['--- INICIO DE COMPARATIVA ASIGNATURAS ---']);
            // Procesar cada usuario (establecimiento)
            foreach ($this->usuarios as $index => $usuario) {

                // Realizar login con AuthController
                $authController = new \App\Http\Controllers\Auth\AuthController();
                $request = new \Illuminate\Http\Request();
                $request->replace($usuario);

                $respuestaLogin = $authController->login($request);
                logger()->info([' > > > ' . $respuestaLogin . ' < < <']);
                $contenidoRespuesta = json_decode($respuestaLogin->getContent(), true);
                $roles = $contenidoRespuesta['roles'];
                $nombreEstablecimiento = $roles['nombre_estab'];

                logger()->info([' > > > ' . $nombreEstablecimiento . ' < < <']);
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

                // Buscar asignaturas pendientes de sincronización
                $asignaturas = UsuarioAsignatura::with([
                    'curso' => function ($query) {
                        $query->select('id', 'nombre', 'letra', 'idGrado')
                            ->orderBy('idGrado')
                            ->orderBy('letra');
                    },
                    'asignatura' => function ($query) {
                        $query->select('id', 'nombre', 'idGrado')
                            ->where('estado', 'Activo');
                    }
                ])
                    ->whereHas('curso', function ($query) {
                        $query->whereNotIn('idGrado', [4, 5]);
                    })
                    ->get();

                if (empty($asignaturas)) {
                    logger()->info(['No tiene asignaturas en el sistema']);
                    $resultados[] = [
                        'usuario' => $usuario['correo'],
                        'estado' => 'success',
                        'mensaje' => 'No hay asignaturas en el sistema',
                    ];
                    continue;
                }

                $cursosAgrupados = [];
                foreach ($asignaturas as $asignatura) {
                    if (isset($asignatura->curso)) {
                        $idGrado = $asignatura->curso->idGrado;
                        $letra = $asignatura->curso->letra;
                        $nombre = $asignatura->curso->nombre;
                        $key = $idGrado . '-' . $letra;

                        if (!isset($cursosAgrupados[$key])) {
                            $cursosAgrupados[$key] = [
                                'idGrado' => $idGrado,
                                'letra' => $letra,
                                'nombre' => $nombre,
                                'asignaturas' => []
                            ];
                        }
                        $cursosAgrupados[$key]['asignaturas'][] = $asignatura->asignatura->nombre;
                    }
                }

                $cursosData = array_values($cursosAgrupados);
                // Preparar la petición para sincronizarEvaluaciones
                $requestSync = new \Illuminate\Http\Request();
                $requestSync->replace(['cursos' => $cursosData]);

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
                $respuestaSinc = $this->asignaturasLD($requestSync);
                if ($respuestaSinc instanceof \Illuminate\Http\JsonResponse) {
                    $resultadoSinc = json_decode($respuestaSinc->getContent(), true);
                } else {
                    $resultadoSinc = $respuestaSinc;
                }

                // Cerrar sesión para el usuario actual
                $authController->logout($requestSync);

                $resultados = [...$resultados, ...$resultadoSinc];
            }
            return response()->json($resultados);
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

    public function asignaturasLD(Request $request)
    {
        try {

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
                logger()->error(['No se encontraron datos de conexión para el establecimiento.']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se encontraron datos de conexión para el establecimiento'
                ], 400);
            }

            $arrayData = [];
            $cursos = $request->input('cursos');
            $arrayData = [
                'rbd' => $establecimiento->rbd,
                'cursos' => $cursos,
            ];

            // * 4 Envia datos a Libro Digital
            $asignaturasFaltantesLd = $this->enviarAsignaturasLD(
                $arrayData,
                $establecimiento
            );

            return response()->json($asignaturasFaltantesLd);
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

    private function enviarAsignaturasLD(array $datosProcesados, $establecimiento)
    {
        try {
            // Primero obtener el token de autenticación
            $client = new \GuzzleHttp\Client();

            // Hacer login para obtener el token (si es necesario)
            $loginResponse = $client->post($establecimiento->link_ld . '/login', [
                'json' => [
                    'rut' => $establecimiento->user_ld,
                    'password' => $this->password
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);

            $loginData = json_decode($loginResponse->getBody()->getContents(), true);
            $token = $loginData['access_token'] ?? $loginData['token'] ?? null;

            if (!$token) {
                logger()->error('Error en sincronización: No se pudo obtener el token de autenticación', [
                    'loginData' => $loginData
                ]);
                return [
                    'status' => 'Error',
                    'message' => 'Error en sincronización: No se pudo obtener el token de autenticación'
                ];
            }

            // Ahora enviar los datos con el token
            $response = $client->post($establecimiento->link_ld . '/comparativa-asignaturas', [
                'json' => $datosProcesados,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);

            $responseContent = $response->getBody()->getContents();
            return json_decode($responseContent, true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            logger()->error('Error de solicitud en sincronización', [
                'message' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);

            return [
                'status' => 'Error',
                'message' => 'Error en solicitud de sincronización: ' . $e->getMessage(),
                'response' => $e->hasResponse() ? json_decode($e->getResponse()->getBody()->getContents(), true) : null
            ];
        } catch (\Exception $e) {
            logger()->error('Excepción general en sincronización', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'Error',
                'message' => 'Error general en sincronización: ' . $e->getMessage()
            ];
        }

        return $resultados;
    }


    // * COMPARATIVA ESTUDIANTES
    public function comparativaEstudiantes()
    {
        // Tiempo de inicio
        $tiempoInicio = microtime(true);

        $resultados = [];

        try {
            logger()->info(['--- INICIO DE MULTI SINCRONIZACION ---']);
            // Procesar cada usuario (establecimiento)
            foreach ($this->usuarios as $index => $usuario) {

                // Realizar login con AuthController
                $authController = new \App\Http\Controllers\Auth\AuthController();
                $request = new \Illuminate\Http\Request();
                $request->replace($usuario);

                $respuestaLogin = $authController->login($request);
                logger()->info([' > > > ' . $respuestaLogin . ' < < <']);
                $contenidoRespuesta = json_decode($respuestaLogin->getContent(), true);
                $roles = $contenidoRespuesta['roles'];
                $nombreEstablecimiento = $roles['nombre_estab'];

                logger()->info([' > > > ' . $nombreEstablecimiento . ' < < <']);
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
                $alumnos = Alumno::whereHas('curso', function ($query) {
                    $query->whereNotIn('idGrado', [4, 5]);
                })
                    ->with(['curso' => function ($query) {
                        $query->orderBy('idGrado')
                            ->orderBy('letra');
                    }])
                    ->get();

                if (empty($alumnos)) {
                    logger()->info(['No tiene alumnos en el sistema']);
                    $resultados[] = [
                        'usuario' => $usuario['correo'],
                        'estado' => 'success',
                        'mensaje' => 'No hay alumnos en el sistema',
                    ];
                    continue;
                }

                $cursosAgrupados = [];

                foreach ($alumnos as $alumno) {
                    if (isset($alumno->curso[0])) {
                        $idGrado = $alumno->curso[0]->idGrado;
                        $letra = $alumno->curso[0]->letra;
                        $key = $idGrado . '-' . $letra;

                        $nombre = $alumno->curso[0]->nombre;

                        if (!isset($cursosAgrupados[$key])) {
                            $cursosAgrupados[$key] = [
                                'idGrado' => $idGrado,
                                'letra' => $letra,
                                'nombre' => $nombre,
                                'estudiantes' => []
                            ];
                        }
                        $cursosAgrupados[$key]['estudiantes'][] = $alumno->rut;
                    }
                }

                $cursosData = array_values($cursosAgrupados);

                // Preparar la petición para sincronizarEvaluaciones
                $requestSync = new \Illuminate\Http\Request();
                $requestSync->replace(['cursos' => $cursosData]);

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
                $respuestaSinc = $this->estudiantesLD($requestSync);
                if ($respuestaSinc instanceof \Illuminate\Http\JsonResponse) {
                    $resultadoSinc = json_decode($respuestaSinc->getContent(), true);
                } else {
                    $resultadoSinc = $respuestaSinc;
                }

                // Cerrar sesión para el usuario actual
                $authController->logout($requestSync);

                $resultados = [...$resultados, ...$resultadoSinc];
            }
            return response()->json($resultados);
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

    public function estudiantesLD(Request $request)
    {
        try {

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
                logger()->error(['No se encontraron datos de conexión para el establecimiento.']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se encontraron datos de conexión para el establecimiento'
                ], 400);
            }

            $arrayData = [];
            $cursos = $request->input('cursos');
            $arrayData = [
                'rbd' => $establecimiento->rbd,
                'cursos' => $cursos,
            ];

            // * 4 Envia datos a Libro Digital
            $estudiantesFaltantesLd = $this->enviarEstudiantesLD(
                $arrayData,
                $establecimiento
            );

            return response()->json($estudiantesFaltantesLd);
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

    private function enviarEstudiantesLD(array $datosProcesados, $establecimiento)
    {
        try {
            // Primero obtener el token de autenticación
            $client = new \GuzzleHttp\Client();

            // Hacer login para obtener el token (si es necesario)
            $loginResponse = $client->post($establecimiento->link_ld . '/login', [
                'json' => [
                    'rut' => $establecimiento->user_ld,
                    'password' => $this->password
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);

            $loginData = json_decode($loginResponse->getBody()->getContents(), true);
            $token = $loginData['access_token'] ?? $loginData['token'] ?? null;

            if (!$token) {
                logger()->error('Error en sincronización: No se pudo obtener el token de autenticación', [
                    'loginData' => $loginData
                ]);
                return [
                    'status' => 'Error',
                    'message' => 'Error en sincronización: No se pudo obtener el token de autenticación'
                ];
            }

            // Ahora enviar los datos con el token
            $response = $client->post($establecimiento->link_ld . '/comparativa-estudiantes', [
                'json' => $datosProcesados,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);

            $responseContent = $response->getBody()->getContents();
            return json_decode($responseContent, true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            logger()->error('Error de solicitud en sincronización', [
                'message' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);

            return [
                'status' => 'Error',
                'message' => 'Error en solicitud de sincronización: ' . $e->getMessage(),
                'response' => $e->hasResponse() ? json_decode($e->getResponse()->getBody()->getContents(), true) : null
            ];
        } catch (\Exception $e) {
            logger()->error('Excepción general en sincronización', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'Error',
                'message' => 'Error general en sincronización: ' . $e->getMessage()
            ];
        }

        return $resultados;
    }


    // * ASIGNATURAS ASIGNADAS
    public function asignaturasAsignadas()
    {
        // Tiempo de inicio
        $tiempoInicio = microtime(true);

        $resultados = [];

        try {
            logger()->info(['--- INICIO DE ASIGNATURAS ASIGNADAS ---']);
            // Procesar cada usuario (establecimiento)
            foreach ($this->usuarios as $index => $usuario) {

                // Realizar login con AuthController
                $authController = new \App\Http\Controllers\Auth\AuthController();
                $request = new \Illuminate\Http\Request();
                $request->replace($usuario);

                $respuestaLogin = $authController->login($request);
                logger()->info([' > > > ' . $respuestaLogin . ' < < <']);
                $contenidoRespuesta = json_decode($respuestaLogin->getContent(), true);
                $roles = $contenidoRespuesta['roles'];
                $nombreEstablecimiento = $roles['nombre_estab'];

                logger()->info([' > > > ' . $nombreEstablecimiento . ' < < <']);
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

                // Buscar asignaturas pendientes de sincronización
                $asignaturas = UsuarioAsignatura::with([
                    'curso' => function ($query) {
                        $query->select('id', 'nombre', 'letra', 'idGrado')
                            ->orderBy('idGrado')
                            ->orderBy('letra');
                    },
                    'asignatura' => function ($query) {
                        $query->select('id', 'nombre', 'idGrado')
                            ->where('estado', 'Activo');
                    },
                    'estabUsuarioRol.usuario' => function ($query) {
                        $query->select('id', 'nombres', 'primerApellido', 'correo');
                    },
                    'estabUsuarioRol' => function ($query) {
                        $query->select('id', 'idUsuario', 'idEstablecimiento', 'idRol');
                    }
                ])
                    ->whereHas('curso', function ($query) {
                        $query->whereNotIn('idGrado', [4, 5]);
                    })
                    ->get();

                if (empty($asignaturas)) {
                    logger()->info(['No tiene asignaturas en el sistema']);
                    $resultados[] = [
                        'usuario' => $usuario['correo'],
                        'estado' => 'success',
                        'mensaje' => 'No hay asignaturas en el sistema',
                    ];
                    continue;
                }

                $cursosAgrupados = [];
                foreach ($asignaturas as $asignaturaUsuario) {
                    if (isset($asignaturaUsuario->curso)) {
                        $idGrado = $asignaturaUsuario->curso->idGrado;
                        $letra = $asignaturaUsuario->curso->letra;
                        $nombre = $asignaturaUsuario->curso->nombre;
                        $key = $idGrado . '-' . $letra;

                        if (!isset($cursosAgrupados[$key])) {
                            $cursosAgrupados[$key] = [
                                'colegio' => $nombreEstablecimiento,
                                'curso' => $nombre . ' ' . $letra,
                                'asignaturas' => []
                            ];
                        }

                        $usuario = null;
                        $rol = null;

                        if ($asignaturaUsuario->estabUsuarioRol) {
                            $rol = [
                                'id' => $asignaturaUsuario->estabUsuarioRol->idRol,
                                'establecimiento' => $asignaturaUsuario->estabUsuarioRol->idEstablecimiento
                            ];

                            if ($asignaturaUsuario->estabUsuarioRol->usuario) {
                                $usuario = [
                                    'id' => $asignaturaUsuario->estabUsuarioRol->usuario->id,
                                    'nombre' => $asignaturaUsuario->estabUsuarioRol->usuario->nombres . ' ' . $asignaturaUsuario->estabUsuarioRol->usuario->primerApellido,
                                    'correo' => $asignaturaUsuario->estabUsuarioRol->usuario->correo,
                                    'rol' => $rol
                                ];
                            }
                        }

                        $cursosAgrupados[$key]['asignaturas'][] = [
                            'nombre' => $asignaturaUsuario->asignatura->nombre,
                            'usuario' => $usuario ?? [
                                'id' => null,
                                'nombre' => 'Sin usuario asignado',
                                'correo' => null,
                                'rol' => null
                            ]
                        ];
                    }
                }

                $cursosData = array_values($cursosAgrupados);

                $resultados = [...$resultados, ...$cursosData];
            }
            return response()->json($resultados);
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
