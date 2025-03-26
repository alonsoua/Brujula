<?php

namespace App\Http\Controllers;

use App\Models\Alumnos_Cursos;
use App\Models\Evaluacion;
use App\Models\EvaluacionIndicador;
use App\Models\EvaluacionNota;
use Illuminate\Http\Request;

class EvaluacionIndicadorController extends Controller
{

    protected $evaluacionNotaController;
    protected $puntajeIndicadorController;
    public function __construct()
    {
        $this->evaluacionNotaController = new EvaluacionNotaController();
        $this->puntajeIndicadorController = new PuntajeIndicadorController();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($idEvaluacion)
    {
        $evaluacionesIndicadores = EvaluacionIndicador::where('idEvaluacion', $idEvaluacion)->get();
        return response()->json($evaluacionesIndicadores, 200);
    }

    public function getIndicadoresUsados($idObjetivo, $tipoObjetivo, $idCurso, $idAsignatura, $idEvaluacion)
    {

        try {
            // Consulta que filtra evaluaciones_indicadores por idObjetivo y tipoObjetivo
            // y relaciona con evaluaciones filtrando por idCurso y idAsignatura
            $indicadoresUsados = EvaluacionIndicador::join('evaluaciones', 'evaluaciones_indicadores.idEvaluacion', '=', 'evaluaciones.id')
                ->where('evaluaciones_indicadores.idObjetivo', $idObjetivo)
                ->where('evaluaciones_indicadores.tipoObjetivo', $tipoObjetivo)
                ->where('evaluaciones.idCurso', $idCurso)
                ->where('evaluaciones.idAsignatura', $idAsignatura)
                ->where('evaluaciones.id', '!=', $idEvaluacion)
                ->select('evaluaciones_indicadores.idIndicador', 'evaluaciones.nombre as nombreEvaluacion')
                ->get();

            return response()->json($indicadoresUsados, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los indicadores usados: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            $user = $request->user()->getUserData();
            $idPeriodo = $user['periodo']['id'];
            // Validar los datos requeridos
            $request->validate([
                'idObjetivo' => 'required|integer',
                'tipoObjetivo' => 'required|string',
                'indicadores' => 'array',
                'indicadores.*.idIndicador' => 'integer',
                'indicadores.*.tipoIndicador' => 'string',
                'idEvaluacion' => 'required|integer',
            ]);

            // Obtener indicadores existentes
            $indicadoresExistentes = EvaluacionIndicador::where('idObjetivo', $request->idObjetivo)
                ->where('idEvaluacion', $request->idEvaluacion)
                ->get()
                ->keyBy('idIndicador');

            // Obtener solo los nuevos indicadores (que no existen actualmente)
            $nuevosIndicadores = collect($request->indicadores)
                ->keyBy('idIndicador')
                ->reject(function ($value, $key) use ($indicadoresExistentes) {
                    return $indicadoresExistentes->has($key);
                });

            // Obtener indicadores a eliminar (están en existentes pero no en el request)
            $indicadoresAEliminar = $indicadoresExistentes->reject(function ($value, $key) use ($request) {
                return collect($request->indicadores)->pluck('idIndicador')->contains($key);
            });

            // Eliminar indicadores que ya no están presentes
            foreach ($indicadoresAEliminar as $indicador) {
                $this->eliminarPuntajesIndicadores($request, $idPeriodo, $request->idObjetivo, $request->tipoObjetivo, $indicador->idIndicador, $indicador->tipoIndicador);
                $evaluacionIndicador = EvaluacionIndicador::where('id', $indicador->id)
                    ->first();
                $evaluacionIndicador->delete();
            }

            // Crear o mantener indicadores
            foreach ($nuevosIndicadores as $idIndicador => $indicadorData) {
                // Crear nuevo indicador
                $evaluacionIndicador = new EvaluacionIndicador([
                    'idObjetivo' => $request->idObjetivo,
                    'tipoObjetivo' => $request->tipoObjetivo,
                    'idIndicador' => $indicadorData['idIndicador'],
                    'tipoIndicador' => $indicadorData['tipoIndicador'],
                    'idEvaluacion' => $request->idEvaluacion,
                ]);
                $evaluacionIndicador->save();

                $evaluacionNota = EvaluacionNota::where('idEvaluacion', $request->idEvaluacion)
                    ->get();
                foreach ($evaluacionNota as $nota) {

                    $params = [
                        'nota' => $nota->nota,
                        'idAlumno' => $nota->idAlumno,
                        'idEvaluacion' => $request->idEvaluacion,
                    ];
                    $newRequest = new Request($params);
                    $newRequest->setUserResolver(function () use ($request) {
                        return $request->user();
                    });
                    // Agregar notas a los nuevos indicadores
                    $this->evaluacionNotaController->store($newRequest);
                }
            }

            // Retornar respuesta
            return response()->json($nuevosIndicadores->values(), 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Nueva función para actualizar los puntajes de indicadores
    protected function eliminarPuntajesIndicadores(Request $request, $idPeriodo, $idObjetivo, $tipoObjetivo, $idIndicador, $tipoIndicador)
    {
        try {
            $idEvaluacion = $request->idEvaluacion;

            $evaluacion = Evaluacion::where('id', $idEvaluacion)->first();
            $alumnos = Alumnos_Cursos::where('idCurso', $evaluacion->idCurso)->get();
            foreach ($alumnos as $alumno) {
                $data = [
                    'idPeriodo' => $idPeriodo,
                    'idCurso' => $evaluacion->idCurso,
                    'idAsignatura' => $evaluacion->idAsignatura,
                    'idObjetivo' => $idObjetivo,
                    'tipoObjetivo' => $tipoObjetivo,
                    'idIndicador' => $idIndicador,
                    'tipoIndicador' => $tipoIndicador === 'Ministerio'
                        ? 'Normal'
                        : 'Interno',
                    'idAlumno' => $alumno->idAlumno,
                    'puntaje' => 0
                ];

                // Crear un nuevo Request con los datos necesarios
                $newRequest = new Request($data);
                $newRequest->setUserResolver(function () use ($request) {
                    return $request->user();
                });

                // Llamar al método update de PuntajeIndicadorController
                $this->puntajeIndicadorController->update($newRequest);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    protected function agregarPuntajesIndicadores(Request $request, $idPeriodo, $idObjetivo, $tipoObjetivo, $idIndicador, $tipoIndicador)
    {
        try {
            $idEvaluacion = $request->idEvaluacion;

            $evaluacion = Evaluacion::where('id', $idEvaluacion)->first();
            $alumnos = Alumnos_Cursos::where('idCurso', $evaluacion->idCurso)->get();
            foreach ($alumnos as $alumno) {
                $data = [
                    'idPeriodo' => $idPeriodo,
                    'idCurso' => $evaluacion->idCurso,
                    'idAsignatura' => $evaluacion->idAsignatura,
                    'idObjetivo' => $idObjetivo,
                    'tipoObjetivo' => $tipoObjetivo,
                    'idIndicador' => $idIndicador,
                    'tipoIndicador' => $tipoIndicador === 'Ministerio'
                        ? 'Normal'
                        : 'Interno',
                    'idAlumno' => $alumno->idAlumno,
                    'puntaje' => 0
                ];

                // Crear un nuevo Request con los datos necesarios
                $newRequest = new Request($data);
                $newRequest->setUserResolver(function () use ($request) {
                    return $request->user();
                });

                // Llamar al método update de PuntajeIndicadorController
                $this->puntajeIndicadorController->update($newRequest);
            }
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
        //
    }
}
