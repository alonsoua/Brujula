<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Establecimiento;
use App\Models\UsuarioEstablecimiento;
use App\Models\model_has_roles;

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
        // Si $user tiene $user->idEstablecimientoActivo, muestra la información
        // del establecimiento activo
        // Si idEstablecimientoActivo es null, es Super Admin o Admin Daem
        $user = $request->user();
        $response = array();
        if (is_null($user->idEstablecimientoActivo)) {
            $admins = User::getAllAdmins();
            foreach ($admins as $adminKey => $admin) {
                if ($admin->avatar) {
                    $admin->avatar = $this->url->to('/').''.Storage::url(
                        'avatars_usuarios/'.$admin->avatar
                    );
                }
                array_push($response, $admin);
            }
        }
        $usuarios = User::getAll($user->idEstablecimientoActivo);

        foreach ($usuarios as $usuarioKey => $usuario) {
            if ($usuario->avatar) {
                $usuario->avatar = $this->url->to('/').''.Storage::url(
                    'avatars_usuarios/'.$usuario->avatar
                );
            }
            array_push($response, $usuario);
        }


        return $response;
    }

    /**
     * Obtiene listado de Docentes
     *
     * @return \Illuminate\Http\Response
     */
    public function getDocentesActivos(Request $request)
    {
        $user = $request->user();
        return User::getDocentesActivos(Null);
        return response($user, 200);
        return User::getDocentesActivos($user->idEstablecimiento);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'email'           => 'required|email|max:80|unique:users',
            'password'        => 'required|min:6|max:30',
            'rut'             => 'required|max:15|unique:users',
            'nombres'         => 'required|max:150',
            'primerApellido'  => 'required|max:100',
            'segundoApellido' => 'required|max:100',
            'estado'          => 'required',
        ]);

        try {

            DB::transaction(function () use ($request) {
                $establecimiento = $request->input('establecimiento');
                $idRol     = $request->input('rol')['id'];
                $nombreRol = $request->input('rol')['title'];
                $rut       = $request->input('rut');
                $avatar    = $request->input('avatar');


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

                $userCreate = $request->user();

                $usuario = User::Create([
                    'email'              => $request->input('email'),
                    'password'           => bcrypt($request->input('password')),
                    'avatar'             => $avatar,
                    'rut'                => $rut,
                    'nombres'            => $request->input('nombres'),
                    'primerApellido'     => $request->input('primerApellido'),
                    'segundoApellido'    => $request->input('segundoApellido'),
                    'idEstablecimientoActivo' => $establecimiento,
                    'rolActivo'          => $nombreRol,
                    'estado'             => $request->input('estado'),
                    'idUsuarioCreated'   => $userCreate['id'],
                ]);

                if ($nombreRol === 'Super Administrador' ||
                    $nombreRol === 'Administrador Daem')
                {
                    // * Relaciona rol por medio de spatie
                    // model_type = App\Models\User
                    $usuario->assignRole($nombreRol);

                } else {
                    $usuarioEstablecimiento = UsuarioEstablecimiento::create([
                        'idUsuario'         => $usuario->id,
                        'idEstablecimiento' => $establecimiento,
                    ]);

                    // * Relaciona rol directo en la Tabla
                    // model_type = App\Models\UsuarioEstablecimiento
                    model_has_roles::create([
                        'role_id'    => $idRol,
                        'model_type' => 'App\Models\UsuarioEstablecimiento',
                        'model_id'   => $usuarioEstablecimiento->id,
                    ]);
                }

                return response(null, 200);
            });
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return User::findOrFail($id);
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
        Request()->validate([
            'email' => 'required|max:80|unique:users,email,'.$id.',id' ,
            'rut' => 'required|max:15|unique:users,rut,'.$id.',id' ,
            'nombres'         => 'required|max:150',
            'primerApellido'  => 'required|max:100',
            'segundoApellido' => 'required|max:100',
        ]);

        try {
            $usuario = User::findOrFail($id);

            $rut    = $request->input('rut');
            $avatar = $request->input('avatar');

            if (!is_null($avatar)) {
                $nombreInsignia = formatNameImage(
                    $avatar
                    , $rut
                );
                if ( !is_null($nombreInsignia) ) {
                    $avatarAntigua = $usuario->avatar;
                    if ($avatarAntigua) {
                        Storage::disk('avatars_usuarios')->delete($avatarAntigua);
                    }
                    saveStorageImagen(
                        'avatars_usuarios'
                        , $avatar
                        , $nombreInsignia
                    );
                    $usuario->avatar = $nombreInsignia;
                }
            } else {
                $avatarAntigua = $usuario->avatar;
                if ($avatarAntigua) {
                    Storage::disk('avatars_usuarios')->delete($avatarAntigua);
                }
                $usuario->avatar = null;
            }

            $nombres = $request->input('nombres');
            $email   = $request->input('email');
            $primerApellido  = $request->input('primerApellido');
            $segundoApellido = $request->input('segundoApellido');

            $usuario->email   = $email;
            $usuario->rut     = $rut;
            $usuario->nombres = $nombres;
            $usuario->primerApellido  = $primerApellido;
            $usuario->segundoApellido = $segundoApellido;
            $usuario->save();

            return response(null, 200);

        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }

    /**
     * Update las vistas del usuario en el sistema.
     *
     * Establecimiento Activo
     * Rol Activo
     * Periodo Activo
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateVistas(Request $request, $id)
    {
        Request()->validate([
            'idEstablecimientoActivo' => 'required',
            'rolActivo' => 'required',
            // 'idPeriodoActivo' => 'required',
        ]);
        try {
            $usuario = User::findOrFail($id);

            $idEstablecimientoActivo = $request->input('idEstablecimientoActivo');
            $rolActivo               = $request->input('rolActivo');
            $idPeriodoActivo         = $request->input('idPeriodoActivo');

            // * Si cambia el establecimiento
            if ($usuario['idEstablecimientoActivo'] != $idEstablecimientoActivo) {

                $idUsuarioEstablecimiento = UsuarioEstablecimiento::getId(
                    $id,
                    $idEstablecimientoActivo
                );
                $existeRol = model_has_roles::getExisteRolInEstablecimiento(
                    $idUsuarioEstablecimiento,
                    $usuario['rolActivo']
                );
                if ($existeRol == false) {
                    $roles = model_has_roles::getRolByModel_id(
                        $idUsuarioEstablecimiento,
                        'UsuarioEstablecimiento'
                    );
                    $rolActivo = $roles[0]['nombre'];
                }

                // consultar establecimiento para obtener periodo Activo
                $establecimiento = Establecimiento::findOrFail($idEstablecimientoActivo);
                $idPeriodoActivo = $establecimiento['idPeriodoActivo'];
            }

            $usuario->idEstablecimientoActivo = $idEstablecimientoActivo;
            $usuario->rolActivo               = $rolActivo;
            $usuario->idPeriodoActivo         = $idPeriodoActivo;
            $usuario->save();

            return response(null, 200);

        } catch (\Throwable $th) {
            return response($th, 500);
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
        //
    }
}
