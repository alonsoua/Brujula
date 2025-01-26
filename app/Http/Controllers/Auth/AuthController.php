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
        $credentials = $request->only('correo', 'password');

        // Paso 1: Verificar credenciales en la tabla estab_usuarios
        $user = \App\Models\Master\Usuario::where('correo', $credentials['correo'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        // Registrar última conexión y aumentar el conteo de conexiones
        $user->update([
            'ultima_conexion' => now(), // Registra la fecha y hora actual
            'conexiones' => $user->conexiones + 1, // Incrementa el conteo de conexiones
        ]);

        // Paso 2: Obtener relación del usuario con roles y establecimiento desde estab_usuarios_roles
        $usuarioRoles = DB::connection('master')
        ->table('estab_usuarios_roles')
            ->where('idUsuario', $user->id)
            ->join('roles', 'roles.id', '=', 'estab_usuarios_roles.idRol')
            ->join('establecimientos', 'establecimientos.id', '=', 'estab_usuarios_roles.idEstablecimiento')
            ->select(
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
            ->get();

        if ($usuarioRoles->isEmpty()) {
            return response()->json(['error' => 'El usuario no tiene roles o establecimientos asociados'], 404);
        }

        // Seleccionar el primer establecimiento para la conexión
        $establecimiento = $usuarioRoles->first();

        // Paso 3: Configurar la conexión dinámica con los datos del establecimiento
        Config::set('database.connections.establecimiento', [
            'driver' => 'mysql',
            'host' => $establecimiento->bd_host ?? env('DB_HOST', '127.0.0.1'),
            'port' => $establecimiento->bd_port ?? env('DB_PORT', '3306'),
            'database' => $establecimiento->bd_name,
            'username' => $establecimiento->bd_user,
            'password' => $establecimiento->bd_pass,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ]);

        DB::purge('establecimiento');
        DB::reconnect('establecimiento');

        // Generar un token de acceso
        $token = $user->createToken('estab-token')->plainTextToken;
        
        // Respuesta
        return response()->json([
            'user' => $user,
            'roles' => $usuarioRoles->map(function ($rol) {
                return [
                    'id_estab' => $rol->idEstablecimiento,
                    'nombre_estab' => $rol->nombre_establecimiento,
                    'id_rol' => $rol->idRol,
                    'name' => $rol->nombre_rol,
                    'guard_name' => $rol->guard_name,
                ];
            }),
            'token' => $token,
        ]);
    }


    /**
     * @OA\Post(
     *     path="/cliente/me",
     *     tags={"cliente-auth"},
     *     summary="Data usuario",
     *     description="Obtiene datos de usuario.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Data necesaria",
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="bearer"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Retorna datos"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid Token"
     *     )
     * )
     */
    // public function authme()
    // {
    //     $usuario = Auth::user();
    //     try {
    //         $usuario['rol_activo'] = $usuario->ClienteUsuarioRol;
    //         $usuario['clientes_usuario'] = $usuario->ClientesUsuario;
    //         $usuario['permisos_usuario'] = Permisos::select(
    //             'action',
    //             'subject'
    //         )
    //             ->where('id_rol', $usuario['rol_activo']->id_rol)
    //             ->where('estado', 1)
    //             ->get();


    //         return response()->json($usuario);
    //     } catch (Exception $e) {
    //         return response()->json(['error' => $e], 500);
    //     }
    // }

    /**
     * @OA\Post(
     *     path="/cliente/logout",
     *     tags={"cliente-auth"},
     *     summary="Cierra sesión",
     *     description="Cierra sesión.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Data necesaria",
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="bearer"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Retorna datos"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid Token"
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json(['message' => 'Haz cerrado sesión.']);
    }
}
