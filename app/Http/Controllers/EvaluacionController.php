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
    public function index(Request $request)
    {
        $user = $request->user()->getUserData();
        $idEstabUsuarioRol = $user['rolActivo']['idEstabUsuarioRol'];
        $evaluaciones = Evaluacion::where('idEstabUsuarioRol', $idEstabUsuarioRol)
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
            ]);

            // Crear la evaluación
            $evaluacion = new Evaluacion([
                'nombre' => $request->nombre,
                'fecha' => $request->fecha,
                'idAsignatura' => $request->idAsignatura,
                'idCurso' => $request->idCurso,
                'idEstabUsuarioRol' => $idEstabUsuarioRol,
                'estado' => 'activo',
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
            ]);

            // Obtener la evaluación existente
            $evaluacion = Evaluacion::findOrFail($id);

            // Actualizar la evaluación
            $evaluacion->update($request->all());

            return response()->json($evaluacion, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            // Obtener la evaluación existente con sus relaciones
            $evaluacion = Evaluacion::with(['evaluacionesIndicadores', 'evaluacionesNotas'])
                ->findOrFail($id);

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
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
