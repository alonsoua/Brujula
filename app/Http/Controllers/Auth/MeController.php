<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UsuarioEstablecimiento;
use App\Models\model_has_roles;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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

        // Define url de avatar
        if ($user->avatar) {
            $user->avatar = $this->url->to('/').''.Storage::url(
                'avatars_usuarios/'.$user['avatar']
            );
        }

        $establecimientos = UsuarioEstablecimiento::
            getEstablecimientosActivosPorUsuario($user->id);

        foreach ($establecimientos as $key => $establecimiento) {
            if ($establecimiento->insignia) {
                $establecimiento->insignia = $this->url->to('/').''.Storage::
                url(
                    'insignias_establecimientos/'.$establecimiento['insignia']
                );
            }
        }

        if ($user['rolActivo'] === 'Super Administrador'
            || $user['rolActivo'] === 'Administrador Daem'
        ) {

            $usersPermissions = $user->getAllPermissions();
            $rol = model_has_roles::getRolByModel_id($user['id'], 'User');

        } else {
            $role = Role::findByName($user['rolActivo']);
            $usersPermissions = $role->getAllPermissions();

            // obtenemos todos los roles de este establecimiento, para asignarlo a roles
            foreach ($establecimientos as $establecimientoKey => $establecimiento) {
                $rol = model_has_roles::getRolByModel_id(
                    $establecimiento['id'],
                    'UsuarioEstablecimiento'
                );
                $roles = array();
                foreach ($rol as $key => $r) {
                    array_push(
                        $roles,
                        $r
                    );
                }
                $establecimientos[$establecimientoKey]['roles'] = $roles;
            }
        }

        $permisos = array();
        array_push($permisos, array(
            'action' => 'read',
            'subject' => 'home'
        ));

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

        return response()->json([
            'id'                      => $user->id,
            'email'                   => $user->email,
            'avatar'                  => $user->avatar,
            'rut'                     => $user->rut,
            'nombres'                 => $user->nombres,
            'primerApellido'          => $user->primerApellido,
            'segundoApellido'         => $user->segundoApellido,
            'idEstablecimientoActivo' => $user->idEstablecimientoActivo,
            'rolActivo'               => $user->rolActivo,
            'estado'                  => $user->estado,
            'establecimientos'        => $establecimientos,
            'ability'                 => $permisos,
        ]);
    }
}
