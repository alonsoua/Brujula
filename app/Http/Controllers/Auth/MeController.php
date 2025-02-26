<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Master\Ajuste;
use App\Models\Master\Estab_usuario_rol;
use App\Models\Master\Periodo;
use App\Models\Master\Rol;
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
        
        $user = $request->user();

        // Obtener rol activo (Master)
        $rolActivo = Estab_usuario_rol::getRolActivo($user->id);

        // Validar si el usuario está activo
        if (!$rolActivo || !$rolActivo->establecimiento) {
            auth()->logout();
            return response()->json(['error' => 'El usuario se encuentra inactivo.'], 400);
        }

        $establecimiento = $rolActivo->establecimiento;

        // Agregar insignia al establecimiento
        if ($establecimiento->insignia) {
            $establecimiento->insignia = $this->url->to('/') . Storage::url(
                'insignias_establecimientos/' . $establecimiento->insignia
            );
        }

        // Obtener los permisos del rol activo (Master)
        $permisos = Rol::rolHasPermisos($rolActivo->idRol);

        // Obtener ajustes del establecimiento (Master)
        $ajustes = Ajuste::getAjustes($establecimiento->id, $establecimiento->idPeriodoActivo);

        if (!$ajustes) {
            return response()->json(['error' => 'El establecimiento no cuenta con los ajustes configurados para el periodo actual.'], 400);
        }

        $periodo = Periodo::find($ajustes->idPeriodo);
        
        return response()->json([
            'id' => $user->id,
            'email' => $user->correo,
            'rut' => $user->rut ?? null,
            'nombres' => $user->nombres ?? null,
            'primerApellido' => $user->primerApellido ?? null,
            'segundoApellido' => $user->segundoApellido ?? null,
            'idEstablecimientoActivo' => $establecimiento->id,
            'periodo' => $periodo,
            'ajustes' => $ajustes,
            'establecimiento' => [
                'id' => $establecimiento->id,
                'nombre' => $establecimiento->nombre,
                'rbd' => $establecimiento->rbd,
                'insignia' => $establecimiento->insignia,
            ],
            'rolActivo' => [
                'id' => $rolActivo->id_rol,
                'nombre' => $rolActivo->rol->name ?? null,
                'guard_name' => $rolActivo->rol->guard_name ?? null,
            ],
            'ability' => $permisos,
        ]);
    }
}
