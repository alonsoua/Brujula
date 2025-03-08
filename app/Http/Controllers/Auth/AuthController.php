<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Master\Cliente_usuario;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use OpenApi\Annotations as OA;

use App\Helpers\ApiResp;
use App\Models\Cliente\Permisos;
use App\Models\Master\Cliente_usuario_rol;
use App\Models\Master\Estab_usuario_rol;
use App\Models\Master\Usuario;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{

    /**
     * @OA\Post(
     *     path="/cliente/login",
     *     tags={"cliente-auth"},
     *     summary="Iniciar sesión",
     *     description="Inicia sesión en el sistema y retorna token de acceso.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Data para el inicio de sesión",
     *         @OA\JsonContent(
     *             required={"correo", "password"},
     *             @OA\Property(property="correo", type="string", format="email", example="monodigital@md.cl"),
     *             @OA\Property(property="password", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Sesión iniciada"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid Sesión"
     *     )
     * )
     */
    public function login(Request $request)
    {
        try {
            $credentials = $request->only('correo', 'password');

            // 🔹 Paso 1: Verificar credenciales en la tabla estab_usuarios
            $user = Usuario::where('correo', $credentials['correo'])->first();
            if (!$user && !Hash::check($credentials['password'], $user->password)) {
                return response()->json(['error' => 'Credenciales inválidas'], 401);
            }

            $this->updateUltimaConexionUser($user);

            // 🔹 Paso 2: Obtener roles y establecimientos del usuario
            $usuarioRoles = $this->obtenerRolesYEstablecimientos($user->id, $request->input('idEstablecimiento'), $request->input('idRol'));
            if ($usuarioRoles->isEmpty()) {
                return response()->json(['error' => 'El usuario no tiene roles o establecimientos asociados'], 404);
            }

            // 🔹 Paso 3: Verificar si hay múltiples roles
            if ($usuarioRoles->count() > 1) {
                $idEstablecimiento = $request->input('idEstablecimiento');
                $idRol = $request->input('idRol');

                if (!$idEstablecimiento || !$idRol) {
                    return response()->json([
                        'usuarioRoles' => $usuarioRoles->map(function ($rol) {
                            return [
                                'idEstablecimiento' => $rol->idEstablecimiento,
                                'nombre_establecimiento' => $rol->nombre_establecimiento,
                                'idRol' => $rol->idRol,
                                'nombre_rol' => $rol->nombre_rol,
                            ];
                        }),
                        'status' => 'Pending'
                    ]);
                }
            }
            $usuarioRoles = $usuarioRoles->first();

            // Actualizar el valor de isLogin en la tabla estab_usuarios_roles
            Estab_usuario_rol::where('idUsuario', $user->id)
                ->where('id', '!=', $usuarioRoles->id)
                ->update(['isLogin' => 0]);
            Estab_usuario_rol::where('id', $usuarioRoles->id)
                ->update(['isLogin' => 1]);

            // 🔹 Paso 4: Actualiza la última conexión y Configurar la conexión dinámica
            $this->updateUltimaConexionRol($usuarioRoles);
            $this->configurarConexionEstablecimiento($usuarioRoles);

            // 🔹 Paso 5: Generar token de acceso
            $token = $user->createToken('estab-token')->plainTextToken;

            // 🔹 Respuesta con usuario, roles y token
            return response()->json([
                'status' => 'success',
                'user' => $user,
                'roles' => $this->formatearRol($usuarioRoles),
                'token' => $token,
            ]);
        } catch (\Throwable $th) {
            Log::error('Error al iniciar sesión:', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Error al iniciar sesión'], 500);
        }
    }

    /**
     * Verifica las credenciales del usuario y retorna el usuario si son correctas.
     */
    private function updateUltimaConexionUser($user)
    {
        $user->update([
            'ultima_conexion' => now(),
            'conexiones' => $user->conexiones + 1,
        ]);
    }

    /**
     * Actualiza la última conexión y el número de conexiones del rol del usuario.
     */
    private function updateUltimaConexionRol($usuarioRol)
    {

        // Buscar el registro específico del usuario en la tabla estab_usuarios_roles
        $registroRol = Estab_usuario_rol::where('id', $usuarioRol->id)->first();

        if ($registroRol) {
            $registroRol->update([
                'ultima_conexion' => now(),
                'conexiones' => $registroRol->conexiones + 1,
            ]);
        }
    }

    /**
     * Obtiene los roles y establecimientos del usuario.
     */
    private function obtenerRolesYEstablecimientos($idUsuario, $idEstablecimiento = null, $idRol = null)
    {
        $query = Estab_usuario_rol::select(
            'estab_usuarios_roles.id',
            'roles.id as idRol',
            'roles.name as nombre_rol',
            'roles.guard_name',
            'establecimientos.id as idEstablecimiento',
            'establecimientos.bd_name',
            'establecimientos.bd_user',
            'establecimientos.bd_pass',
            'establecimientos.bd_host',
            'establecimientos.bd_port',
            'establecimientos.nombre as nombre_establecimiento'
        )
            ->join('roles', 'roles.id', '=', 'estab_usuarios_roles.idRol')
            ->join('establecimientos', 'establecimientos.id', '=', 'estab_usuarios_roles.idEstablecimiento')
            ->where('idUsuario', $idUsuario);

        if ($idEstablecimiento) {
            $query->where('idEstablecimiento', $idEstablecimiento);
        }

        if ($idRol) {
            $query->where('idRol', $idRol);
        }

        return $query->get();
    }

    /**
     * Configura la conexión dinámica con la base de datos del establecimiento.
     */
    private function configurarConexionEstablecimiento($establecimiento)
    {
        $password = decrypt($establecimiento->bd_pass);

        // logger()->info('Configurando LOGIN conexión para el establecimiento:', [
        //     'host' => $establecimiento->bd_host,
        //     'port' => $establecimiento->bd_port,
        //     'database' => $establecimiento->bd_name,
        //     'username' => $establecimiento->bd_user,
        // ]);

        Config::set('database.connections.establecimiento', [
            'driver' => 'mysql',
            'host' => $establecimiento->bd_host ?? env('DB_HOST', '127.0.0.1'),
            'port' => $establecimiento->bd_port ?? env('DB_PORT', '3306'),
            'database' => $establecimiento->bd_name,
            'username' => $establecimiento->bd_user,
            'password' => $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ]);

        DB::purge('establecimiento');
        DB::reconnect('establecimiento');
        DB::setDefaultConnection('establecimiento');
    }

    /**
     * Formatea los datos del rol para la respuesta.
     */
    private function formatearRol($rol)
    {
        return [
            'id_estab' => $rol->idEstablecimiento,
            'nombre_estab' => $rol->nombre_establecimiento,
            'id_rol' => $rol->idRol,
            'name' => $rol->nombre_rol,
            'guard_name' => $rol->guard_name,
        ];
    }

    /**
     * @OA\Post(
     *     path="/cliente/logout",
     *     tags={"cliente-auth"},
     *     summary="Cierra sesión",
     *     description="Cierra sesión y revoca el token.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Sesión cerrada exitosamente"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token inválido o sesión no encontrada"
     *     )
     * )
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            // Si usas Sanctum
            if (method_exists($user, 'currentAccessToken')) {
                $user->currentAccessToken()->delete();
            }

            // Si usas Passport
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            return response()->json(['message' => 'Sesión cerrada exitosamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cerrar sesión', 'details' => $e->getMessage()], 500);
        }
    }
}
