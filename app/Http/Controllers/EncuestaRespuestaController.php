<?php

namespace App\Http\Controllers;

use App\Models\EncuestaRespuesta;
use Illuminate\Http\Request;

class EncuestaRespuestaController extends Controller
{
    public function create(Request $request)
    {
        // Crear o actualizar respuesta
        $respuesta = EncuestaRespuesta::updateOrCreate(
            [
                'encuesta_pregunta_id' => $request->encuesta_pregunta_id,
                'encuesta_participante_id' => $request->encuesta_participante_id
            ],
            $request->only(['encuesta_opcion_id', 'texto_respuesta'])
        );

        return response()->json($respuesta, 201);
    }
}
