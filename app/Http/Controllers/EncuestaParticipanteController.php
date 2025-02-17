<?php

namespace App\Http\Controllers;

use App\Models\EncuestaParticipante;
use App\Models\EncuestaPregunta;
use Illuminate\Http\Request;

class EncuestaParticipanteController extends Controller
{
    public function index()
    {
        // Listar participantes con porcentaje de avance
        $participantes = EncuestaParticipante::with('encuesta')->get();

        foreach ($participantes as $participante) {
            $totalPreguntas = EncuestaPregunta::where('encuesta_id', $participante->encuesta_id)->count();
            $respuestas = $participante->respuestas()->count();
            $participante->avance = $totalPreguntas ? ($respuestas / $totalPreguntas) * 100 : 0;
            $participante->detalle_avance = "$respuestas/$totalPreguntas";
        }

        return $participantes;
    }

    public function create(Request $request)
    {
        // Crear participante
        $participante = EncuestaParticipante::create($request->all());
        return response()->json($participante, 201);
    }

    public function update(Request $request, $participante_id)
    {
        // Actualizar participante
        $participante = EncuestaParticipante::findOrFail($participante_id);
        $participante->update($request->all());

        return response()->json($participante, 200);
    }
}
