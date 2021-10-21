<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Establecimiento;
use App\Models\UsuarioEstablecimiento;
use App\Models\model_has_roles;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\UrlGenerator;
use Spatie\Permission\Models\Role;

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
                array_push($response, $admin);
            }
        }
        $usuarios = User::getAll($user->idEstablecimientoActivo);

        foreach ($usuarios as $usuarioKey => $usuario) {
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

                $usuario = User::Create([
                    'name'               => 'a',
                    'email'              => $request->input('email'),
                    'password'           => bcrypt($request->input('password')),
                    'rut'                => $request->input('rut'),
                    'nombres'            => $request->input('nombres'),
                    'primerApellido'     => $request->input('primerApellido'),
                    'segundoApellido'    => $request->input('segundoApellido'),
                    'idEstablecimientoActivo' => $establecimiento,
                    'rolActivo'          => $nombreRol,
                    'estado'             => $request->input('estado'),
                ]);

                if ($nombreRol === 'Super Administrador' ||
                    $nombreRol === 'Administrador Daem')
                {
                    // * Asigna rol por medio de spatie
                    // model_type = App\Models\User
                    $usuario->assignRole($nombreRol);

                } else {
                    $usuarioEstablecimiento = UsuarioEstablecimiento::create([
                        'idUsuario'         => $usuario->id,
                        'idEstablecimiento' => $establecimiento,
                    ]);

                    // * Asigna rol directo a la Tabla
                    $usuarioEstablecimiento = model_has_roles::create([
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
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
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
