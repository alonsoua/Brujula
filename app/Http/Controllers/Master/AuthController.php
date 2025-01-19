<?php

namespace App\Http\Controllers\Master;

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

        // Buscar al usuario por correo en la base de datos
        $user = \App\Models\Master\User::where('correo', $credentials['correo'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        // Obtener datos del establecimiento asociado
        $establecimiento = DB::connection('master')->table('estab')->where('id', $user->id_establecimiento)->first();

        if ($establecimiento) {
            // Configurar la conexión dinámica
            Config::set('database.connections.establecimiento', [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
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
            $token = $user->createToken('master-token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);
        }

        return response()->json(['error' => 'No se encontró un establecimiento asociado'], 404);
    }

    // /**
    //  * @OA\Post(
    //  *     path="/cliente/me",
    //  *     tags={"cliente-auth"},
    //  *     summary="Data usuario",
    //  *     description="Obtiene datos de usuario.",
    //  *     @OA\RequestBody(
    //  *         required=true,
    //  *         description="Data necesaria",
    //  *         @OA\JsonContent(
    //  *             required={"token"},
    //  *             @OA\Property(property="token", type="bearer"),
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=201,
    //  *         description="Retorna datos"
    //  *     ),
    //  *     @OA\Response(
    //  *         response=400,
    //  *         description="Invalid Token"
    //  *     )
    //  * )
    //  */
    // public function authme()
    // {
    //     try {
    //         $usuario = Auth::user();
    //         $usuario['rol_activo'] = Auth::user()->ClienteUsuarioRol;
    //         $usuario['clientes_usuario'] = Auth::user()->ClientesUsuario;
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
