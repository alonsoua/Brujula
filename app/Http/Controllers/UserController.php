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
        $user = $request->user();
        $admins = User::getAllAdmins();
        $usuarios = User::getAll($user->idEstablecimientoActivo);
        $response = array();

        foreach ($admins as $key => $admin) {
            array_push($response, $admin);
        }

        foreach ($usuarios as $key => $usuario) {
            array_push($response, $usuario);
        }
        // return response($admins, 200);
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
        // Request()->validate([
        //     'idEstablecimientoActivo' => 'required|max:10|unique:establecimientos,rbd,'.$id.',id' ,
        //     'rolActivo' => 'required|max:200',
        //     'idPeriodoActivo' => 'required|email|max:80',
        //     'telefono' => 'required|max:25',
        //     'direccion' => 'required|max:250',
        //     'dependencia' => 'required',
        //     'estado' => 'required',
        // ]);
        try {
            $usuario = User::findOrFail($id);

            $idEstablecimientoActivo = $request->input('idEstablecimientoActivo');
            $rolActivo               = $request->input('rolActivo');
            $idPeriodoActivo         = $request->input('idPeriodoActivo');

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
