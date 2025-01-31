<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Ajuste;
use App\Models\Master\Estab_usuario_rol;
use App\Models\UsuarioEstablecimiento;
use App\Models\model_has_roles;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class MeController extends Controller
{
    protected $url;

    public function __construct(UrlGenerator $url)
    {
        $this->middleware(['auth:establecimiento']);
        $this->url = $url;
    }


    public function me(Request $request)
    {
        dd('Entró a me()');
        return 111;
        $user = $request->user();


        dd($user);
        // Validar si el usuario está activo
        if ($user->estado == false) {
            auth()->logout();
            return response('Usuario inactivo', 500);
        }

        // Obtener insignia del establecimiento activo
        $rolActivo = Estab_usuario_rol::with('establecimiento')
            ->where('id_estab_usuario', $user->id)
            ->where('estado', 1)
            ->first();

        if (!$rolActivo || !$rolActivo->establecimiento) {
            return response()->json(['error' => 'No se encontró un rol o establecimiento activo.'], 404);
        }

        $establecimiento = $rolActivo->establecimiento;

        // Agregar insignia al establecimiento
        if ($establecimiento->insignia) {
            $establecimiento->insignia = $this->url->to('/') . Storage::url(
                'insignias_establecimientos/' . $establecimiento->insignia
            );
        }

        // Obtener los permisos del rol activo
        $permisos = DB::table('role_has_permissions')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->where('role_has_permissions.role_id', $rolActivo->id_rol)
            ->select('permissions.name')
            ->get()
            ->map(function ($permiso) {
                $val = explode('_', $permiso->name);
                return [
                    'action' => $val[0] ?? null,
                    'subject' => $val[1] ?? null,
                ];
            })
            ->toArray();

        // Obtener ajustes del cliente (tabla `ajustes` en la base de datos de establecimiento)
        $ajustes = Ajuste::getAjustes($establecimiento->id);

        return response()->json([
            'id' => $user->id,
            'email' => $user->correo,
            'rut' => $user->rut ?? null,
            'nombres' => $user->nombre ?? null,
            'primerApellido' => $user->primer_apellido ?? null,
            'segundoApellido' => $user->segundo_apellido ?? null,
            'idEstablecimientoActivo' => $establecimiento->id,
            'idPeriodoActivo' => null, // Ajustar si necesitas implementarlo más adelante
            'ajustes' => $ajustes,
            'rolActivo' => [
                'id' => $rolActivo->id_rol,
                'nombre' => $rolActivo->rol->name ?? null,
                'guard_name' => $rolActivo->rol->guard_name ?? null,
            ],
            'estado' => $user->estado,
            'establecimientos' => [
                [
                    'id' => $establecimiento->id,
                    'nombre' => $establecimiento->nombre,
                    'insignia' => $establecimiento->insignia,
                ],
            ],
            'ability' => $permisos,
        ]);
    }
}
