<?php

namespace App\Http\Controllers;

use App\Models\Establecimiento;
use App\Models\Master\Estab_usuario_rol;
use App\Models\Master\Usuario;
use App\Models\UsuarioEstablecimiento;
use App\Models\model_has_roles;
use App\Models\UsuarioAsignatura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    protected $url;

    public function __construct(UrlGenerator $url)
    {
        // $this->middleware(['auth:api']);
        $this->url = $url;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user()->getUserData();
        return Usuario::getUsuariosPorEstablecimiento($user['establecimiento']['id']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function findCorreo($correo)
    {
        try {
            $usuario = Usuario::where('correo', $correo)
                ->with(['estabUsuariosRoles' => function ($query) {
                    $query->where('estado', '!=', 2)
                        ->with(['establecimiento', 'rol']);
                }])
                ->first();
            return response()->json($usuario);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Ocurrió un error al procesar la solicitud.',
                'mensaje' => $th->getMessage(),
                'archivo' => $th->getFile(),
                'linea' => $th->getLine()
            ], 404);
        }
    }

    /**
     * Obtiene listado de Docentes
     *
     * @return \Illuminate\Http\Response
     */
    // public function getDocentesActivos(Request $request)
    // {
    //     $user = $request->user();
    //     return User::getDocentesActivos(Null);
    //     return response($user, 200);
    //     return User::getDocentesActivos($user->idEstablecimiento);
    // }

    /**
     * Obtiene listado de Asignaturas por docente
     *
     * @return \Illuminate\Http\Response
     */
    public function getDocenteAsignaturas(Request $request, $idEstabUsuarioRol)
    {
        $user = $request->user()->getUserData();
        $asignaturas = UsuarioAsignatura::getAsignaturaActiva(
            $idEstabUsuarioRol,
            $user['periodo']['id']
        );
        return response($asignaturas, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {

            $request->validate([
                'correo'          => 'required|email|max:80|unique:master.usuarios',
                'password'        => 'required|min:6|max:30',
                'rut'             => 'required|max:15|unique:master.usuarios',
                'nombres'         => 'required|max:150',
                'primerApellido'  => 'required|max:100',
                'segundoApellido' => 'required|max:100',
                'estado'          => 'required',
            ]);

            DB::transaction(function () use ($request) {

                $user = $request->user()->getUserData();
                $usuarioId = $user['id'];
                $idEstablecimiento = $user['establecimiento']['id'];

                $avatar    = $request->input('avatar');
                $rut       = $request->input('rut');
                $idRol     = $request->input('idRol')['id'];
                $nombreRol = $request->input('idRol')['title'];


                if ( !is_null( $avatar ) ) {
                    $nombreAvatar = formatNameImage(
                        $avatar
                        , $rut
                    );

                    saveStorageImagen(
                        'avatars_usuarios'
                        , $avatar
                        , $nombreAvatar
                    );
                    $avatar = $nombreAvatar;
                }

                $usuario = Usuario::Create([
                    'correo'              => $request->input('correo'),
                    'password'           => bcrypt($request->input('password')),
                    'avatar'             => $avatar,
                    'rut'                => $rut,
                    'nombres'            => $request->input('nombres'),
                    'primerApellido'     => $request->input('primerApellido'),
                    'segundoApellido'    => $request->input('segundoApellido'),
                    'idEstablecimientoActivo' => $idEstablecimiento,
                    'rolActivo'          => $nombreRol,
                    'estado'             => $request->input('estado'),
                    'idUsuarioCreated'   => $usuarioId,
                ]);


                $estabUsuarioRol = Estab_usuario_rol::create([
                    'idEstablecimiento' => $idEstablecimiento,
                    'idUsuario'         => $usuario->id,
                    'idRol'             => $idRol,
                ]);

                // Asignaturas Habilitadas
                $asignaturas = $request->input('asignaturas');
                if ($asignaturas) {
                    foreach ($asignaturas as $key => $asignatura) {
                        UsuarioAsignatura::create([
                            'idEstabUsuarioRol' => $estabUsuarioRol->id,
                            'idCurso'           => $asignatura['idCurso'],
                            'idAsignatura'      => $asignatura['idAsignatura'],
                        ]);
                    }
                }
                return response($request->input('asignaturas'), 200);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación.',
                'mensajes' => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Ocurrió un error al procesar la solicitud.',
                'mensaje' => $th->getMessage(),
                'archivo' => $th->getFile(),
                'linea' => $th->getLine()
            ], 404);
        }
    }

    public function addRol(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'correo' => 'required|max:80|unique:master.usuarios,correo,' . $id,
                'rut' => 'required|max:15|unique:master.usuarios,rut,' . $id,
                'nombres' => 'required|max:150',
                'primerApellido' => 'required|max:100',
                'segundoApellido' => 'required|max:100',
            ]);

            $user = $request->user()->getUserData();
            $idEstablecimiento = $user['establecimiento']['id'];

            $usuario = Usuario::findOrFail($id);

            $rut = $validatedData['rut'];
            $avatar = $request->input('avatar');


            if (!is_null($avatar)) {
                $nombreInsignia = formatNameImage($avatar, $rut);
                if (!is_null($nombreInsignia)) {
                    $avatarAntigua = $usuario->avatar;
                    if ($avatarAntigua) {
                        Storage::disk('avatars_usuarios')->delete($avatarAntigua);
                    }
                    saveStorageImagen('avatars_usuarios', $avatar, $nombreInsignia);
                    $usuario->avatar = $nombreInsignia;
                }
            } else {
                $avatarAntigua = $usuario->avatar;
                if ($avatarAntigua) {
                    Storage::disk('avatars_usuarios')->delete($avatarAntigua);
                }
                $usuario->avatar = null;
            }

            $usuario->correo = $validatedData['correo'];
            $usuario->rut = $rut;
            $usuario->nombres = $validatedData['nombres'];
            $usuario->primerApellido = $validatedData['primerApellido'];
            $usuario->segundoApellido = $validatedData['segundoApellido'];

            $idRol     = $request->input('idRol')['id'];
            $estabUsuarioRol = Estab_usuario_rol::create([
                'idEstablecimiento' => $idEstablecimiento,
                'idUsuario'         => $usuario->id,
                'idRol'             => $idRol,
            ]);

            // Asignaturas Habilitadas
            $asignaturas = $request->input('asignaturas');
            if ($asignaturas) {
                foreach ($asignaturas as $key => $asignatura) {
                    UsuarioAsignatura::create([
                        'idEstabUsuarioRol' => $estabUsuarioRol->id,
                        'idCurso'           => $asignatura['idCurso'],
                        'idAsignatura'      => $asignatura['idAsignatura'],
                    ]);
                }
            }

            $usuario->save();
            return response($request->input('asignaturas'), 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación.',
                'mensajes' => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Ocurrió un error al procesar la solicitud.',
                'mensaje' => $th->getMessage(),
                'archivo' => $th->getFile(),
                'linea' => $th->getLine()
            ], 404);
        }
    }

    public function updateEstado(Request $request, $id)
    {
        try {
            $Estab_usuario_rol = Estab_usuario_rol::findOrFail($id);

            $estado = $request->input('estado');
            $Estab_usuario_rol->estado = $estado;
            $Estab_usuario_rol->save();

            return response(null, 200);
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'correo' => 'required|max:80|unique:master.usuarios,correo,' . $id,
                'rut' => 'required|max:15|unique:master.usuarios,rut,' . $id,
                'nombres' => 'required|max:150',
                'primerApellido' => 'required|max:100',
                'segundoApellido' => 'required|max:100',
            ]);

            $usuario = Usuario::findOrFail($id);

            $rut = $validatedData['rut'];
            $avatar = $request->input('avatar');

            if (!is_null($avatar)) {
                $nombreInsignia = formatNameImage($avatar, $rut);
                if (!is_null($nombreInsignia)) {
                    $avatarAntigua = $usuario->avatar;
                    if ($avatarAntigua) {
                        Storage::disk('avatars_usuarios')->delete($avatarAntigua);
                    }
                    saveStorageImagen('avatars_usuarios', $avatar, $nombreInsignia);
                    $usuario->avatar = $nombreInsignia;
                }
            } else {
                $avatarAntigua = $usuario->avatar;
                if ($avatarAntigua) {
                    Storage::disk('avatars_usuarios')->delete($avatarAntigua);
                }
                $usuario->avatar = null;
            }

            $usuario->correo = $validatedData['correo'];
            $usuario->rut = $rut;
            $usuario->nombres = $validatedData['nombres'];
            $usuario->primerApellido = $validatedData['primerApellido'];
            $usuario->segundoApellido = $validatedData['segundoApellido'];

            // Asignaturas Habilitadas
            $idEstabUsuarioRol = $request->input('idEstabUsuarioRol');
            UsuarioAsignatura::deleteUsuarioAsignaturas($idEstabUsuarioRol);

            $asignaturas = $request->input('asignaturas');
            if ($asignaturas) {
                foreach ($asignaturas as $key => $asignatura) {
                    UsuarioAsignatura::create([
                        'idEstabUsuarioRol' => $idEstabUsuarioRol,
                        'idCurso' => $asignatura['idCurso'],
                        'idAsignatura' => $asignatura['idAsignatura'],
                    ]);
                }
            }

            $usuario->save();

            return response(null, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación.',
                'mensajes' => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Ocurrió un error al procesar la solicitud.',
                'mensaje' => $th->getMessage(),
                'archivo' => $th->getFile(),
                'linea' => $th->getLine()
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $user = Estab_usuario_rol::findOrFail($id);
            $user->estado = 2; // 2 = Eliminado
            $user->save();
            return response(Null, 200);

        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }
}
