<?php

namespace App\Models\Master;

use App\Models\Master\Rol as MasterRol;
use App\Models\Rol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class Estab_usuario extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Tabla de bd y bd
     */
    protected $table = 'estab_usuarios';
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
        'nombre',
        'conexiones',
        'ultima_conexion',
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
    public function estab()
    {
        return $this->belongsTo(Estab::class, 'id_estab');
    }
    
    /**
     * Relación con Rol.
     */
    public function roles()
    {
        return $this->belongsTo(MasterRol::class, 'id_estab_rol');
    }

    /**
     * DATA DEL USUARIO LOGEADO.
     */
    public function getEstabBD()
    {
        // Buscar todos los establecimientos activos asociados al usuario
        $estabsActivos = Estab_usuario_rol::with(['establecimiento'])
        ->where('id_estab_usuario', $this->id)
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
        $id_estab_usuario = $this['id_estab_usuario'];

        $estabsRoles = Estab_usuario_rol::from('estabs_usuarios_rol as cur')
            ->select(
                // CLIENTE
                'cl.id_estab',
                'cl.nombre_estab',
                // 'cl.nombre_contacto',
                // 'cl.celular',
                // 'cl.correo',
                // 'cl.logotipo',
                // 'cl.pais',
                // 'cl.codigo_pais',
                // 'cl.moneda',
                // 'cl.timezone',
                // 'cl.verificacion_email',
                // 'cl.tipo_estab',
                // 'cl.created_at',
                // 'cl.updated_at',

                // ROLES
                'cr.id_estab_rol',
                'cr.nombre as nombre_rol',
                'cr.abreviatura',
            )
            ->join('estabs as cl', 'cur.id_estab', '=', 'cl.id_estab')
            ->join('estabs_roles as cr', 'cur.id_rol', '=', 'cr.id_estab_rol')
            ->where('cur.id_estab_usuario', $id_estab_usuario)

            ->where('cur.estado', 'activo')
            ->where('cl.estado', 'activo')

            ->get();

        $result = [];

        foreach ($estabsRoles as $item) {
            if (!isset($result[$item->id_estab])) {
                $result[$item->id_estab] = [
                    'id_estab' => $item->id_estab,

                    'bd_name' => $item->bd_name,
                    'bd_pass' => $item->bd_pass,
                    'bd_user' => $item->bd_user,

                    'nombre_estab' => $item->nombre_estab,
                    'roles' => []
                ];
            }
            $result[$item->id_estab]['roles'][] = [
                'id_rol' => $item->id_estab_rol,
                'nombre' => $item->nombre_rol,
                'abreviatura' => $item->abreviatura
            ];
        }

        return $result = array_values($result);
    }

    public function getEstabUsuarioRol()
    {
        // Verificar si existe un rol activo para este usuario
        $rolActivo = Estab_usuario_rol::with(['rol', 'establecimiento']) // Carga las relaciones necesarias
        ->where('id_estab_usuario', $this->id) // Cambié $this->id_estab_usuario por $this->id
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

    public function getEstabBDAttribute()
    {
        return $this->getEstabBD() ?? null;
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
