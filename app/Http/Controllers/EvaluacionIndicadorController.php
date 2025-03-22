<?php

namespace App\Http\Controllers;

use App\Models\EvaluacionIndicador;
use Illuminate\Http\Request;

class EvaluacionIndicadorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
            // Validar los datos requeridos
            $request->validate([
                'idObjetivo' => 'required|integer',
                'idIndicador' => 'required|integer',
                'idEvaluacion' => 'required|integer',
            ]);

            // Crear el EvaluacionIndicador
            $evaluacionIndicador = new EvaluacionIndicador($request->all());

            // Guardar el EvaluacionIndicador
            $evaluacionIndicador->save();

            // Retornar respuesta
            return response()->json($evaluacionIndicador, 201);
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
        //
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
                'idObjetivo' => 'required|integer',
                'idIndicador' => 'required|integer',
                'idEvaluacion' => 'required|integer',
            ]);

            // Obtener el EvaluacionIndicador existente
            $evaluacionIndicador = EvaluacionIndicador::findOrFail($id);

            // Actualizar el EvaluacionIndicador
            $evaluacionIndicador->update($request->all());

            // Retornar respuesta
            return response()->json($evaluacionIndicador, 200);
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
        // Al eliminar una evaluación indicador, todas las notas ingresadas 
        // en cada indicador de puntajes_indicadores, deben ser eliminadas.
    }
}
