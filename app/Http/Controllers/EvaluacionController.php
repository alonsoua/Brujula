<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluacionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $idSubperiodo)
    {
        $user = $request->user()->getUserData();
        $idEstabUsuarioRol = $user['rolActivo']['idEstabUsuarioRol'];
        $evaluaciones = Evaluacion::where('idEstabUsuarioRol', $idEstabUsuarioRol)
            ->where('idSubperiodo', $idSubperiodo)
            ->with(['curso' => function ($query) {
                $query->select('id', 'nombre as nombreCurso', 'letra')
                    ->addSelect(DB::raw("CONCAT(nombre, ' ', letra) as nombreCurso"));
            }, 'asignatura' => function ($query) {
                $query->select('id', 'nombre as nombreAsignatura');
        }, 'evaluacionesIndicadores.objetivos' => function ($query) {
            $query->select('id', 'abreviatura', 'tipo', 'priorizacionInterna', 'nombre');
            }])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($evaluaciones, 200);
    }

    public function getEvaluacionesAsignatura($idAsignatura, $idSubperiodo)
    {
        try {
            $evaluaciones = Evaluacion::where('idAsignatura', $idAsignatura)
                ->where('idSubperiodo', $idSubperiodo)
                ->where('estado', 'activo')
                ->with(['evaluacionesIndicadores' => function ($query) {
                    $query->with(['indicador' => function ($query) {
                        $query->select('id', 'nombre');
                    }, 'objetivos' => function ($query) {
                        $query->select('id', 'nombre');
                    }]);
                }])
                ->orderBy('fecha', 'asc')
                ->get();
            return response()->json($evaluaciones, 200);
        } catch (\Exception $e) {
            logger()->info(['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getEvaluacionesCurso($idCurso, $idSubperiodo)
    {
        $query = Evaluacion::where('idSubperiodo', $idSubperiodo);

        if ($idCurso != 0) {
            $query->where('idCurso', $idCurso);
        }

        $evaluaciones = $query->with(['asignatura' => function ($query) {
            $query->select('id', 'nombre as nombreAsignatura');
        }, 'estabUsuarioRol' => function ($query) {
            $query->with(['usuario' => function ($query) {
                $query->select('id', 'nombres', 'primerApellido', 'segundoApellido');
            }]);
        }, 'curso' => function ($query) {
            $query->select('id')
                ->addSelect(DB::raw("CONCAT(nombre, ' ', letra) as nombreCurso"));
        }])
            ->get();
        return response()->json($evaluaciones, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Obtener el usuario y su rol activo
            $user = $request->user()->getUserData();
            $idEstabUsuarioRol = $user['rolActivo']['idEstabUsuarioRol'];

            // Validar los datos requeridos
            $request->validate([
                'nombre' => 'required|string',
                'fecha' => 'required|date',
                'idAsignatura' => 'required|integer',
                'idCurso' => 'required|integer',
                'idSubperiodo' => 'required|integer',
            ]);

            // Verificar si ya existe una evaluación con los mismos datos
            $evaluacionExistente = Evaluacion::where('nombre', $request->nombre)
                ->where('fecha', $request->fecha)
                ->where('idAsignatura', $request->idAsignatura)
                ->where('idCurso', $request->idCurso)
                ->first();

            if ($evaluacionExistente) {
                return response()->json([
                    'error' => 'Ya existe una evaluación con el mismo nombre, fecha, asignatura y curso.'
                ], 422);
            }

            // Crear la evaluación
            $evaluacion = new Evaluacion([
                'nombre' => $request->nombre,
                'fecha' => $request->fecha,
                'idAsignatura' => $request->idAsignatura,
                'idCurso' => $request->idCurso,
                'idSubperiodo' => $request->idSubperiodo,
                'idEstabUsuarioRol' => $idEstabUsuarioRol,
                'estado' => 'activo',
                'estado_sync' => 'no_sync',
            ]);

            // Guardar la evaluación
            $evaluacion->save();

            // Retornar respuesta
            return response()->json($evaluacion, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Obtener la evaluación junto con sus relaciones
        $evaluacion = Evaluacion::with(['evaluacionesIndicadores', 'evaluacionesNotas'])
            ->findOrFail($id);

        // Retornar la evaluación y sus relaciones en formato JSON
        return response()->json($evaluacion);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            // Validar los datos requeridos
            $request->validate([
                'nombre' => 'required|string',
                'fecha' => 'required|date',
                'idAsignatura' => 'required|integer',
                'idCurso' => 'required|integer',
                'idSubperiodo' => 'required|integer',
            ]);

            // Obtener la evaluación existente
            $evaluacion = Evaluacion::findOrFail($id);

            // Actualizar la evaluación
            $params = $request->all();
            if ($evaluacion->estado_sync === 'sync') {
                $params['estado_sync'] = 'de_sync';
            }
            $evaluacion->update($params);

            return response()->json($evaluacion, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Actualiza el estado de sincronización de todas las evaluaciones de un curso
     *
     * @param  int  $idCurso
     * @return \Illuminate\Http\Response
     */
    public function actualizarEstadoSyncCurso($idCurso)
    {
        try {
            // Buscar todas las evaluaciones del curso
            $evaluaciones = Evaluacion::where('idCurso', $idCurso)->get();

            // Actualizar el estado_sync de cada evaluación
            foreach ($evaluaciones as $evaluacion) {
                $evaluacion->update(['estado_sync' => 'de_sync']);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Estado de sincronización actualizado correctamente',
                'evaluaciones_actualizadas' => $evaluaciones->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar el estado de sincronización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            // Obtener la evaluación existente con sus relaciones
            $evaluacion = Evaluacion::with(['evaluacionesIndicadores', 'evaluacionesNotas'])
                ->findOrFail($id);

            $user = $request->user()->getUserData();
            if ($evaluacion->estado_sync === 'sync') {
                $client = new \GuzzleHttp\Client();
                $loginResponse = $client->post($user['establecimiento']['link_ld'] . '/login', [
                    'json' => [
                        'rut' => $user['establecimiento']['user_ld'],
                        'password' => '123456'
                    ],
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
                $client->delete($user['establecimiento']['link_ld'] . '/evaluacion/' . $evaluacion->id_evaluacion_ld, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                // $responseContent = $response->getBody()->getContents();
                // $resultadoCurso = json_decode($responseContent, true);
                // return response()->json($resultadoCurso, 200);
            }

            // Obtener las relaciones
            $evaluacionesIndicadores = $evaluacion->evaluacionesIndicadores;
            $evaluacionesNotas = $evaluacion->evaluacionesNotas;

            // Crear instancia del controlador de notas
            $evaluacionNotaController = new EvaluacionNotaController();

            // 3.- Eliminar puntajes_indicadores enviando nota 0 para cada alumno
            foreach ($evaluacionesNotas as $nota) {
                $request = new Request([
                    'nota' => 0,
                    'idAlumno' => $nota->idAlumno,
                    'idEvaluacion' => $id
                ]);

                // Mantener el usuario actual en el nuevo Request
                $request->setUserResolver(function () {
                    return request()->user();
                });

                $evaluacionNotaController->store($request);
            }

            // 4.- Eliminar evaluaciones_indicadores
            foreach ($evaluacionesIndicadores as $indicador) {
                $indicador->delete();
            }

            // Finalmente eliminar la evaluación
            $evaluacion->delete();

            return response()->json(['message' => 'La evaluación y sus registros relacionados han sido eliminados exitosamente'], 200);
        } catch (\Exception $e) {
            logger()->info(['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
