<?php

namespace App\Http\Controllers;

use App\Models\EncuestaParticipante;
use App\Models\EncuestaRespuesta;
use App\Models\Master\Establecimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class EncuestaRespuestaController extends Controller
{
    public function create(Request $request)
    {
        // Iniciar una transacción para asegurar integridad de datos
        // Obtener el participante asociado
        return DB::transaction(function () use ($request) {

            // Crear o actualizar la respuesta
            $respuesta = EncuestaRespuesta::updateOrCreate(
                [
                    'encuesta_pregunta_id' => $request->encuesta_pregunta_id,
                    'encuesta_participante_id' => $request->encuesta_participante_id
                ],
                $request->only(['encuesta_opcion_id', 'texto_respuesta'])
            );

            $participante = EncuestaParticipante::find($request['encuesta_participante_id']);

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

    public function createPublica(Request $request)
    {
        $this->getConexionPublica($request['rbd']);
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
}
