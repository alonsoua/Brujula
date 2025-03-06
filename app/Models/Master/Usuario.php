<?php

namespace App\Models\Master;

use App\Models\Master\Ajuste;
use App\Models\Master\Rol as MasterRol;
use App\Models\Rol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Tabla de bd y bd
     */
    protected $table = 'usuarios';
    protected $connection = 'master';

    
    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the auto-incrementing ID.
     */
    protected $keyType = 'int';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'correo',
        'password',
        'avatar',
        'rut',
        'nombres',
        'primerApellido',
        'segundoApellido',
        'ultima_conexion',
        'conexiones',
        'estado',
        'idUsuarioCreated',
        'idUsuarioUpdated',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Relación con Estab.
     */
    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'idEstablecimiento');
    }

    /**
     * Relación con Rol.
     */
    public function roles()
    {
        return $this->belongsTo(MasterRol::class, 'idEstabUsuarioRol', 'id');
    }

    public function estabUsuariosRoles()
    {
        return $this->hasMany(Estab_usuario_rol::class, 'idUsuario', 'id');
    }


    /**
     * DATA DEL USUARIO LOGEADO.
     */
    public function getEstablecimientoBD()
    {
        // Buscar todos los establecimientos activos asociados al usuario
        $estabsActivos = Estab_usuario_rol::with(['establecimiento'])
            ->where('idUsuario', $this->id)
            ->where('estado', 1)
            ->get();

        // Si no existe ningún establecimiento activo, emitir un error
        if ($estabsActivos->isEmpty()) {
            throw new \Exception('No se encontró ningún establecimiento activo asociado al usuario.');
        }

        // Si existe más de un establecimiento activo, emitir un error
        if ($estabsActivos->count() > 1) {
            throw new \Exception('Se encontraron múltiples establecimientos activos asociados al usuario.');
        }

        // Tomar el único establecimiento activo
        $estabActivo = $estabsActivos->first();

        // Verificar que la relación con el establecimiento existe
        if (!$estabActivo->establecimiento) {
            throw new \Exception('El establecimiento activo no tiene datos asociados.');
        }

        // Retornar los datos de conexión del establecimiento
        return [
            'bd_name' => $estabActivo->establecimiento->bd_name,
            'bd_user' => $estabActivo->establecimiento->bd_user,
            'bd_pass' => $estabActivo->establecimiento->bd_pass,
            'bd_host' => $estabActivo->establecimiento->bd_host,
            'bd_port' => $estabActivo->establecimiento->bd_port,
        ];
    }

    public function getEstabsUsuario()
    {
        $idUsuario = $this['idUsuario'];

        $estabsRoles = Estab_usuario_rol::from('estabs_usuarios_rol as cur')
            ->select(
                // ESTABLECIMIENTO
                'cl.id as idEstablecimiento',
                'cl.nombre as nombre_establecimiento',

                // ROLES
                'cr.id as idRol',
                'cr.nombre as nombre_rol',
            )
            ->join('establecimientos as cl', 'cur.idEstablecimiento', '=', 'cl.id')
            ->join('roles as cr', 'cur.idRol', '=', 'cr.id')
            ->where('cur.idUsuario', $idUsuario)

            ->where('cur.estado', 'activo')
            ->where('cl.estado', 'activo')

            ->get();

        $result = [];

        foreach ($estabsRoles as $item) {
            if (!isset($result[$item->idEstablecimiento])) {
                $result[$item->id_estab] = [
                    'id_estab' => $item->idEstablecimiento,

                    'bd_name' => $item->bd_name,
                    'bd_pass' => $item->bd_pass,
                    'bd_user' => $item->bd_user,

                    'nombre_establecimiento' => $item->nombre_establecimiento,
                    'roles' => []
                ];
            }
            $result[$item->id_estab]['roles'][] = [
                'idRol' => $item->idRol,
                'nombre' => $item->nombre_rol,
            ];
        }

        return $result = array_values($result);
    }

    public function getEstabUsuarioRol()
    {
        // Verificar si existe un rol activo para este usuario
        $rolActivo = Estab_usuario_rol::with(['rol', 'establecimiento']) // Carga las relaciones necesarias
            ->where('idUsuario', $this->id) // Cambié $this->idUsuario por $this->id
            ->where('estado', 1) // Solo considera roles activos
            ->first();

        // Depuración de datos
        if (!$rolActivo) {
            dd('No se encontró un rol activo para este usuario.');
        }

        // Depuración de relaciones
        if (!$rolActivo->rol || !$rolActivo->establecimiento) {
            dd('Faltan relaciones: ', $rolActivo->toArray());
        }

        // Obtener los permisos asociados al rol activo
        $permisos = DB::table('role_has_permissions')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->where('role_has_permissions.role_id', $rolActivo->rol->id)
            ->select('permissions.id', 'permissions.name', 'permissions.guard_name')
            ->get()
            ->map(function ($permiso) {
                return [
                    'id' => $permiso->id,
                    'name' => $permiso->name,
                    'guard_name' => $permiso->guard_name,
                ];
            })
            ->toArray();

        // Retornar datos del rol, permisos y establecimiento
        return [
            'rol' => [
                'id' => $rolActivo->rol->id,
                'nombre' => $rolActivo->rol->name,
                'guard_name' => $rolActivo->rol->guard_name,
            ],
            'permisos' => $permisos,
            'establecimiento' => [
                'id' => $rolActivo->establecimiento->id,
                'nombre' => $rolActivo->establecimiento->nombre,
            ],
        ];
    }

    public static function getUserData()
    {
        // Obtener usuario autenticado
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        // Obtener rol activo
        $rolActivo = Estab_usuario_rol::getRolActivo($user->id);
        if (!$rolActivo) {
            return null;
        }

        $establecimiento = $rolActivo->establecimiento;
        if (!$establecimiento) {
            return null;
        }

        $ajustes = Ajuste::getAjustes($establecimiento->id, $establecimiento->idPeriodoActivo);
        if (!$ajustes) {
            return null;
        }
        
        $periodo = $ajustes->idPeriodo ? Periodo::find($ajustes->idPeriodo) : null;

        return [
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
                'insignia' => $establecimiento->insignia,
                'rbd' => $establecimiento->rbd,
            ],
            'rolActivo' => [
                'id' => $rolActivo->idRol,
                'idEstabUsuarioRol' => $rolActivo->id,
                'nombre' => $rolActivo->rol->name ?? null,
            ],
        ];
    }

    /**
     * Obtener todos los usuarios de un establecimiento con su respectivo rol.
     *
     * @param int $idEstablecimiento
     * @return \Illuminate\Support\Collection
     */
    public static function getUsuariosPorEstablecimiento($idEstablecimiento)
    {
        return self::select(
            'usuarios.*',
            'estab_usuarios_roles.id as idEstabUsuarioRol',
            'estab_usuarios_roles.estado',
            'estab_usuarios_roles.idRol',
            'estab_usuarios_roles.ultima_conexion as ultimaConexionRol',
            'roles.name as nombreRol'
        )
        ->join('estab_usuarios_roles', 'usuarios.id', '=', 'estab_usuarios_roles.idUsuario')
        ->join('roles', 'estab_usuarios_roles.idRol', '=', 'roles.id')
        ->where('estab_usuarios_roles.idEstablecimiento', $idEstablecimiento)
            ->where('estab_usuarios_roles.estado', '!=', 2)
            ->orderBy('usuarios.id')
            ->get();
    }

    

    public function getUserDataAttribute()
    {
        return $this->getUserData() ?? null;
    }

    public function getEstabBDAttribute()
    {
        return $this->getEstablecimientoBD() ?? null;
    }

    public function getEstabsUsuarioAttribute()
    {
        return $this->getEstabsUsuario() ?? null;
    }

    public function getEstabUsuarioRolAttribute()
    {
        return $this->getEstabUsuarioRol() ?? null;
    }
}
