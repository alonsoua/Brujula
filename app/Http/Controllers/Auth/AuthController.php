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
        dd(111);
        // * VALIDA REQUEST
        $data = $request->validate([
            'correo' => ['required', 'email', 'exists:clientes_usuarios,correo'],
            'password' => ['required', 'min:6'],
        ]);

        // * BUSCA USUARIO
        $user = Cliente_usuario::join('clientes_usuarios_rol as cur', 'cur.id_cliente_usuario', '=', 'clientes_usuarios.id_cliente_usuario')
            ->where('clientes_usuarios.correo', $data['correo'])
            ->where('cur.estado', 'activo')
            ->first();

        // * VALIDACIONES USER
        if (!$user || !Hash::check($data['password'], $user->password)) {
            Log::error('Cliente-Auth login => Las credenciales ingresadas no coinciden con nuestros registros.');
            return ApiResp::error('Las credenciales ingresadas no coinciden con nuestros registros.', 400);
        }

        // * USER DATA
        // $usuario = $user;
        // $usuario['clientes_usuario'] = $user->ClientesUsuario;


        // $usuario['permisos_usuario'] = Permisos::select(
        //     'id_permiso',
        //     'action',
        //     'subject',
        // )
        //     ->where('id_rol', $user['id_rol']->id_rol)
        //     ->where('estado', 1)
        //     ->get();

        $user->tokens()->delete();
        $token = $user->createToken('api_token')->plainTextToken;
        $cookie = cookie(
            'api_token',
            $token,
            env('COOKIE_LIFETIME', 60 * 24 * 7),
            env('COOKIE_PATH', '/'),
            env('COOKIE_DOMAIN', null),
            env('COOKIE_SECURE', false),
            env('COOKIE_HTTP_ONLY', false),
            false,
            env('COOKIE_SAME_SITE', 'Lax')
        );

        return response()->json([
            'user' => $user,
        ], 200)->cookie($cookie);
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
