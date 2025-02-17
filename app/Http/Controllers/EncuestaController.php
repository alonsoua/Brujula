<?php

namespace App\Http\Controllers;

use App\Models\Encuesta;
use App\Models\EncuestaPregunta;
use App\Models\EncuestaOpcion;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PreguntasImport;
use App\Models\Master\Rol;
use Illuminate\Support\Facades\DB;

class EncuestaController extends Controller
{
    public function index()
    {
        // Listar encuestas
        return Encuesta::all();
    }

    public function findOne($encuesta_id)
    {
        // Encontrar una encuesta con sus preguntas y opciones
        return Encuesta::with(['preguntas.opciones'])->findOrFail($encuesta_id);
    }

    public function findRoles($tipo)
    {
        $validTipos = ['Interna' => 'Interno', 'Externa' => 'Externo'];

        return isset($validTipos[$tipo])
            ? Rol::select('id', 'name')
            ->where('tipo', $validTipos[$tipo])
            ->when($tipo === 'Interna', fn($query) => $query->where('id', '>', 3))
            ->get()
            : collect();
    }

    public function create(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                // Convertir roles a JSON si es necesario
                $request->merge([
                    'roles' => is_array($request->roles) ? json_encode($request->roles) : $request->roles
                ]);

                // Crear encuesta
                $encuesta = Encuesta::create($request->only([
                    'nombre',
                    'descripcion',
                    'tipo',
                    'roles',
                    'imagen',
                    'estado',
                    'usuario_id'
                ]));

                // Insertar preguntas y opciones dentro de la transacción
                foreach ($request->preguntas as $preguntaData) {
                    $pregunta = $encuesta->preguntas()->create($preguntaData);

                    foreach ($preguntaData['opciones'] as $opcionData) {
                        $pregunta->opciones()->create($opcionData);
                    }
                }

                return response()->json($encuesta, 201);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $encuesta_id)
    {
        try {
            return DB::transaction(function () use ($request, $encuesta_id) {
                // Convertir roles a JSON si es necesario
                $request->merge([
                    'roles' => is_array($request->roles) ? json_encode($request->roles) : $request->roles
                ]);

                // Buscar la encuesta
                $encuesta = Encuesta::findOrFail($encuesta_id);
                $encuesta->update($request->only([
                    'nombre',
                    'descripcion',
                    'tipo',
                    'roles',
                    'imagen',
                    'estado',
                    'usuario_id'
                ]));

                // Manejar preguntas y opciones
                foreach ($request->preguntas as $preguntaData) {
                    // Si la pregunta tiene un ID, actualizamos; si no, creamos una nueva
                    $pregunta = EncuestaPregunta::updateOrCreate(
                        ['id' => $preguntaData['id'] ?? null, 'encuesta_id' => $encuesta_id],
                        [
                            'numero' => $preguntaData['numero'],
                            'titulo' => $preguntaData['titulo'],
                            'tipo_pregunta' => $preguntaData['tipo_pregunta'],
                            'subcategoria_id' => $preguntaData['subcategoria_id'] ?? null
                        ]
                    );

                    // Manejar opciones dentro de la pregunta
                    foreach ($preguntaData['opciones'] as $opcionData) {
                        EncuestaOpcion::updateOrCreate(
                            ['id' => $opcionData['id'] ?? null, 'encuesta_pregunta_id' => $pregunta->id],
                            [
                                'opcion' => $opcionData['opcion'],
                                'texto' => $opcionData['texto']
                            ]
                        );
                    }
                }

                return response()->json($encuesta, 200);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete($encuesta_id)
    {
        // Eliminar encuesta y sus datos en cascada
        $encuesta = Encuesta::findOrFail($encuesta_id);
        $encuesta->delete();

        return response()->json(null, 204);
    }

    public function importarPreguntas(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'descripcion' => 'required|string',
            'isInterna' => 'required|boolean',
            'participantes' => 'nullable|array',
            'documento' => 'required|file|mimes:xlsx,csv',
            'opciones' => 'required|array',
            'opciones.*.orden' => 'required|integer',
            'opciones.*.opcion' => 'required|string',
            'opciones.*.descripcion' => 'nullable|string',
        ]);

        $encuesta = Encuesta::create($request->only(['nombre', 'descripcion', 'isInterna', 'participantes', 'usuario_id']));

        Excel::import(new PreguntasImport($encuesta, $request->opciones), $request->file('documento'));

        return response()->json($encuesta->load('preguntas.opciones'), 201);
    }
}
