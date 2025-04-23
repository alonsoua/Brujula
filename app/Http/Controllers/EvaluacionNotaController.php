<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use App\Models\EvaluacionNota;
use App\Models\EvaluacionIndicador;
use App\Models\PuntajeIndicador;
use Illuminate\Http\Request;

class EvaluacionNotaController extends Controller
{
    protected $puntajeIndicadorController;

    public function __construct()
    {
        $this->puntajeIndicadorController = new PuntajeIndicadorController();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $idEvaluacion)
    {
        try {
            $evaluacionNotas = EvaluacionNota::where('idEvaluacion', $idEvaluacion)->get();
            return response()->json($evaluacionNotas, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Error de validación: ' . $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getNotasAsignatura($idAsignatura, $idSubperiodo)
    {
        return Evaluacion::join('evaluaciones_notas', 'evaluaciones.id', '=', 'evaluaciones_notas.idEvaluacion')
            ->where('evaluaciones.idAsignatura', $idAsignatura)
            ->where('evaluaciones.idSubperiodo', $idSubperiodo)
            ->where('evaluaciones.estado', 'activo')
            ->select('evaluaciones_notas.*')
            ->get();
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
            $idUsuario = $user['id'];
            $idPeriodo = $user['periodo']['id'];
            // Validar los datos requeridos
            $request->validate([
                'nota' => 'required|numeric',
                'idAlumno' => 'required|integer',
                'idEvaluacion' => 'required|integer',
            ]);

            // Verificar si la nota es 0, en cuyo caso eliminar
            if ($request->nota == 0) {
                EvaluacionNota::where('idEvaluacion', $request->idEvaluacion)
                    ->where('idAlumno', $request->idAlumno)
                    ->delete();

                $this->actualizarPuntajesIndicadores($request, $idPeriodo);
                return response()->json(['message' => 'Evaluación nota eliminada debido a que la nota es 0'], 200);
            }

            // Crear o actualizar la evaluación nota
            $evaluacionNota = EvaluacionNota::updateOrCreate(
                [
                    'idEvaluacion' => $request->idEvaluacion,
                    'idAlumno' => $request->idAlumno
                ],
                [
                    'nota' => $request->nota,
                    'idUsuario_created' => $idUsuario,
                    'idUsuario_updated' => $idUsuario
                ]
            );

            // Verificar si la evaluación nota fue creada o actualizada
            if ($evaluacionNota->wasRecentlyCreated) {
                $evaluacionNota->idUsuario_created = $idUsuario;
            } else {
                $evaluacionNota->idUsuario_updated = $idUsuario;
            }
            $evaluacionNota->save();

            // Llamar a la función para actualizar los puntajes de indicadores
            $this->actualizarPuntajesIndicadores($request, $idPeriodo);

            // Retornar respuesta
            return response()->json($evaluacionNota, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Nueva función para actualizar los puntajes de indicadores
    protected function actualizarPuntajesIndicadores(Request $request, $idPeriodo)
    {

        try {
            $idEvaluacion = $request->idEvaluacion;
            $idAlumno = $request->idAlumno;

            $evaluacion = Evaluacion::where('id', $idEvaluacion)->first();
            $evaluacionesIndicadores = EvaluacionIndicador::where('idEvaluacion', $idEvaluacion)->get();

            foreach ($evaluacionesIndicadores as $evaluacionIndicador) {


                // PROMEDIAR
                $promedioIndicador = Evaluacion::join('evaluaciones_indicadores', 'evaluaciones_indicadores.idEvaluacion', '=', 'evaluaciones.id')
                    ->join('evaluaciones_notas', 'evaluaciones_notas.idEvaluacion', '=', 'evaluaciones.id')
                    ->where('evaluaciones.estado', 'activo')
                    ->where('evaluaciones.idCurso', $evaluacion->idCurso)
                    ->where('evaluaciones.idSubperiodo', $evaluacion->idSubperiodo)
                    ->where('evaluaciones_indicadores.idIndicador', $evaluacionIndicador->idIndicador)
                    ->where('evaluaciones_indicadores.tipoIndicador', $evaluacionIndicador->tipoIndicador)
                    ->where('evaluaciones_notas.idAlumno', $idAlumno)
                    ->pluck('evaluaciones_notas.nota');

                $puntaje = $promedioIndicador->isEmpty() ? 0 : $promedioIndicador->avg();

                logger()->info(['puntaje' => $puntaje]);

                $tipoIndicador = $evaluacionIndicador->tipoIndicador === 'Ministerio'
                    ? 'Normal'
                    : 'Interno';
                $data = [
                    'idPeriodo' => $idPeriodo,
                    'idCurso' => $evaluacion->idCurso,
                    'idAsignatura' => $evaluacion->idAsignatura,
                    'idObjetivo' => $evaluacionIndicador->idObjetivo,
                    'tipoObjetivo' => $evaluacionIndicador->tipoObjetivo,
                    'idIndicador' => $evaluacionIndicador->idIndicador,
                    'tipoIndicador' => $tipoIndicador,
                    'idAlumno' => $idAlumno,
                    'puntaje' => $puntaje
                ];

                // Crear un nuevo Request con los datos necesarios
                $newRequest = new Request($data);
                $newRequest->setUserResolver(function () use ($request) {
                    return $request->user();
                });

                $this->puntajeIndicadorController->update($newRequest);
            }
        } catch (\Exception $e) {
            logger()->error('Error al actualizar los puntajes de indicadores', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'traza' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
