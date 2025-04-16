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
            $nota = $request->nota;

            $evaluacion = Evaluacion::where('id', $idEvaluacion)->first();
            $evaluacionesIndicadores = EvaluacionIndicador::where('idEvaluacion', $idEvaluacion)->get();

            foreach ($evaluacionesIndicadores as $evaluacionIndicador) {
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
                    'puntaje' => $nota
                ];

                // Crear un nuevo Request con los datos necesarios
                $newRequest = new Request($data);
                $newRequest->setUserResolver(function () use ($request) {
                    return $request->user();
                });

                $puntajeIndicador = PuntajeIndicador::select('puntajes_indicadores.puntaje')
                    ->where('puntajes_indicadores.idPeriodo', $idPeriodo)
                    ->where('puntajes_indicadores.idCurso', $evaluacion->idCurso)
                    ->where('puntajes_indicadores.idAsignatura', $evaluacion->idAsignatura)
                    ->where('puntajes_indicadores.idIndicador', $evaluacionIndicador->idIndicador)
                    ->where('puntajes_indicadores.idAlumno', $idAlumno)
                    ->where('puntajes_indicadores.tipoIndicador', $tipoIndicador)
                    ->where('puntajes_indicadores.estado', 'Activo')
                    ->value('puntaje');

                if ($nota > $puntajeIndicador) {
                    // Llamar al método update de PuntajeIndicadorController
                    $this->puntajeIndicadorController->update($newRequest);
                }
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
