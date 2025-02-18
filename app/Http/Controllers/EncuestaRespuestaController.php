<?php

namespace App\Http\Controllers;

use App\Models\EncuestaParticipante;
use App\Models\EncuestaRespuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EncuestaRespuestaController extends Controller
{
    public function create(Request $request)
    {
        // Iniciar una transacción para asegurar integridad de datos
        return DB::transaction(function () use ($request) {
            // Crear o actualizar la respuesta
            $respuesta = EncuestaRespuesta::updateOrCreate(
                [
                    'encuesta_pregunta_id' => $request->encuesta_pregunta_id,
                    'encuesta_participante_id' => $request->encuesta_participante_id
                ],
                $request->only(['encuesta_opcion_id', 'texto_respuesta'])
            );

            // Obtener el participante asociado
            $participante = EncuestaParticipante::find($request->encuesta_participante_id);

            // Si el estado es "Sin Iniciar", actualizar a "En Proceso"
            if ($participante && $participante->estado === 'Sin Iniciar') {
                $participante->update(['estado' => 'En Proceso']);
            }

            return response()->json([
                'respuesta' => $respuesta,
                'participante_estado' => $participante->estado ?? 'Sin Iniciar'
            ], 201);
        });
    }
}
