<?php

namespace App\Http\Controllers;

use App\Models\Encuesta;
use App\Models\EncuestaParticipante;
use App\Models\EncuestaPregunta;
use Illuminate\Http\Request;

class EncuestaParticipanteController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user()->getUserData();
            $rolId = $user['rolActivo']['id'];
            $usuarioId = $user['id'];

            // 🔹 Obtener encuestas filtradas por estado "Publicada", tipo "Interna" y que contengan el rolId
            $encuestas = Encuesta::select(
                'id as encuesta_id',
                'nombre',
                'descripcion',
                'imagen',
                'created_at'
            )
                ->where('estado', 'Publicada')
                ->where('tipo', 'Interna')
                ->whereRaw("JSON_CONTAINS(roles, CAST(? AS JSON))", [$rolId])
                ->get();

            // 🔹 Obtener todos los participantes existentes en una sola consulta
            $participantes = EncuestaParticipante::whereIn('encuesta_id', $encuestas->pluck('encuesta_id'))
            ->where('usuario_id', $usuarioId)
                ->where('rol_id', $rolId)
                ->get()
                ->keyBy('encuesta_id'); // 🔹 Indexar por encuesta_id para acceso rápido


            // 🔹 Transformar encuestas y crear `EncuestaParticipante` si no existe
            $encuestas->transform(function ($encuesta) use ($usuarioId, $rolId, $participantes) {
                if (isset($participantes[$encuesta->encuesta_id])) {
                    // 🔹 Si ya existe el participante, asignamos los datos
                    $encuesta->encuesta_participante_id = $participantes[$encuesta->encuesta_id]->id;
                    $encuesta->encuesta_participante_estado = $participantes[$encuesta->encuesta_id]->estado;
                    $encuesta->encuesta_participante_fecha_inicio = $participantes[$encuesta->encuesta_id]->fecha_inicio
                        ? $participantes[$encuesta->encuesta_id]->fecha_inicio->format('d-m-Y H:i')
                        : null;
                } else {
                    // 🔹 Antes de crear, verificar nuevamente si existe en la BD
                    $nuevoParticipante = EncuestaParticipante::firstOrCreate(
                        [
                            'usuario_id' => $usuarioId,
                            'encuesta_id' => $encuesta->encuesta_id,
                            'rol_id' => $rolId
                        ],
                        [
                            'estado' => 'Sin Iniciar'
                        ]
                    );

                    // 🔹 Asignamos los datos creados
                    $encuesta->encuesta_participante_id = $nuevoParticipante->id;
                    $encuesta->encuesta_participante_estado = $nuevoParticipante->estado;
                    $encuesta->encuesta_participante_fecha_inicio = $nuevoParticipante->fecha_inicio
                        ? $nuevoParticipante->fecha_inicio->format('d-m-Y H:i')
                        : null;
                }

                return $encuesta;
            });

            // 🔹 Retornar la lista sin key "encuestas"
            return response()->json($encuestas, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
