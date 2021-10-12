<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Establecimiento;
use App\Models\UsuarioEstablecimiento;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\UrlGenerator;

class UserController extends Controller
{
    protected $url;

    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return User::orderBy('name')->get();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // return response($request, 200);
        $request->validate([
            'email'          => 'required|email|max:80|unique:users',
            'password'        => 'required|min:6|max:30',
            'rut'             => 'required|max:15|unique:users',
            'nombres'         => 'required|max:150',
            'primerApellido'  => 'required|max:100',
            'segundoApellido' => 'required|max:100',
            'estado'          => 'required',
        ]);

        // try {

            // DB::transaction(function () use ($request) {

                $usuario = User::Create([
                    'correo'          => $request->input('correo'),
                    'password'        => bcrypt($request->input('password')),
                    'rut'             => $request->input('rut'),
                    'nombre'          => $request->input('nombres'),
                    'primerApellido'  => $request->input('primerApellido'),
                    'segundoApellido' => $request->input('segundoApellido'),
                    'idEstablecimientoActivo' => null,
                    'estado'          => $request->input('estado'),
                ]);

                $idEstablecimientoActivo = null;
                $establecimientos = $request->input('establecimientos');
                foreach ($establecimientos as $key => $establecimiento) {
                    $idEstablecimientoActivo = $key == 0
                        ? $establecimiento['id']
                        : $idEstablecimientoActivo;
                    UsuarioEstablecimiento::create([
                        'idUsuario'         => $usuario->id,
                        'idEstablecimiento' => $establecimiento['id'],
                    ]);
                }

                // editar idEstablecimientoActivo


                return response(null, 200);
            // });

        // } catch (\Throwable $th) {
        //     return response($th, 500);
        // }
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
