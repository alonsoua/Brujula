<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Storage;

class MeController extends Controller
{
    protected $url;

    public function __construct(UrlGenerator $url)
    {
        $this->middleware(['auth:api']);
        $this->url = $url;
    }


    public function __invoke(Request $request)
    {
        $user = $request->user();
        if ($user->estado == 'Inactivo') {
            auth()->logout();
            return response('Usuario Inactivo', 500);
        }
        $usersPermissions = $user->getAllPermissions();

        $permisos = array();
        $array = array(
            'action' => 'read',
            'subject' => 'home'
        );
        array_push(
            $permisos,
            $array
        );
        foreach ($usersPermissions as $key2 => $userPermission) {
            $val = explode( '_', $userPermission['name']);
            $array = array(
                'action' => $val[0],
                'subject' => $val[1]
            );
            array_push(
                $permisos,
                $array
            );
        }
        $rol = $user->getRoleNames();
        // if ($user->imagen) {
        //     $user->imagen = $this->url->to('/').''.Storage::url('images_users/'.$user['imagen']);
        // }
        // 'imagen' => $user->imagen,

        return response()->json([
            'id'                      => $user->id,
            'email'                   => $user->email,
            'rut'                     => $user->rut,
            'nombres'                 => $user->nombres,
            'primerApellido'          => $user->primerApellido,
            'segundoApellido'         => $user->segundoApellido,
            'idEstablecimientoActivo' => $user->idEstablecimientoActivo,
            'rol'                     => $rol,
            'estado'                  => $user->estado,
            'ability'                 => $permisos,
        ]);
    }
}
