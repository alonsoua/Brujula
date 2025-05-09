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
