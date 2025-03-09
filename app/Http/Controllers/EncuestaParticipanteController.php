<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Encuesta;
use App\Models\EncuestaParticipante;
use App\Models\EncuestaPregunta;
use App\Models\Master\Establecimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

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

    private function getConexionPublica($rbd)
    {
        // 🔹 Buscar el tenant (establecimiento) según el RBD
        $estab = Establecimiento::where('rbd', $rbd)->firstOrFail();

        $password = decrypt($estab['bd_pass']);

        // 🔹 Configurar la conexión dinámica a la base de datos del establecimiento
        Config::set('database.connections.establecimiento', [
            'driver' => 'mysql',
            'host' => $estab['bd_host'] ?? '127.0.0.1',
            'port' => $estab['bd_port'] ?? '3306',
            'database' => $estab['bd_name'],
            'username' => $estab['bd_user'],
            'password' => $password, // 👈 Asegurar que la contraseña es válida
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        Artisan::call('config:clear');

        DB::purge('establecimiento');
        DB::reconnect('establecimiento');
        DB::setDefaultConnection('establecimiento');
    }

    public function createOrUpdatePublico(Request $request)
    {
        try {

            $validatedData = $request->validate([
                'rbd' => 'required',
                'rut' => 'required',
                'nombre' => 'required',
                'primerApellido' => 'required',
                'segundoApellido' => 'required',
                'encuesta_id' => 'required|integer'
            ]);
            $this->getConexionPublica($validatedData['rbd']);

            $alumno = Alumno::where('rut', $validatedData['rut'])->first();

            if ($alumno) {
                $validatedData['usuario_id'] = $alumno->id;
                $curso = $alumno->cursos()->latest()->first();
                $validatedData['curso_id'] = $curso ? $curso->id : null;
                $validatedData['rol_id'] = 10;
            } else {
                $validatedData['usuario_id'] = null;
                $validatedData['curso_id'] = null;
                $validatedData['rol_id'] = 11;
            }

            $participante = EncuestaParticipante::updateOrCreate(
                [
                    'rut' => $validatedData['rut'],
                    'encuesta_id' => $validatedData['encuesta_id']
                ],
                [
                    'nombre' => $validatedData['nombre'],
                    'primerApellido' => $validatedData['primerApellido'],
                    'segundoApellido' => $validatedData['segundoApellido'],
                    'curso_id' => $validatedData['curso_id'],
                    'usuario_id' => $validatedData['usuario_id'],
                    'rol_id' => $validatedData['rol_id'],
                    'fecha_inicio' => now(),
                    'estado' => 'En Proceso'
                ]
            );

            return response()->json($participante, $participante->wasRecentlyCreated ? 201 : 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $participante_id)
    {
        // Actualizar participante
        $participante = EncuestaParticipante::findOrFail($participante_id);
        $participante->update($request->all());

        return response()->json($participante, 200);
    }
}
