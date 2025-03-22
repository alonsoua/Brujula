<?php

namespace App\Http\Controllers;

use App\Models\EvaluacionNota;
use Illuminate\Http\Request;

class EvaluacionNotaController extends Controller
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        try {
            // Validar los datos requeridos
            $validatedData = $request->validate([
                'nota' => 'required|numeric',
                'tipoIndicador' => 'required|string',
                'idAlumno' => 'required|integer',
                'idEvaluacion' => 'required|integer',
                'idUsuarioCreated' => 'required|integer',
                'idUsuarioUpdated' => 'integer', // Este campo puede ser opcional
            ]);

            // Crear o actualizar la evaluación nota
            $evaluacionNota = EvaluacionNota::updateOrCreate(
                [
                    'idEvaluacion' => $request->idEvaluacion,
                    'idAlumno' => $request->idAlumno
                ],
                $validatedData
            );

            // Retornar respuesta
            return response()->json($evaluacionNota, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
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
        //
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
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
