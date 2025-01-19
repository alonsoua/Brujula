<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
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
    // public function roles()
    // {
    //     return $this->belongsTo(Roles::class, 'id_estab_rol');
    // }

    public function getEstabBDAttribute()
    {
        return $this->getEstabBD() ?? null;
    }

    public function getEstabBD()
    {
        $id_estab_usuario = $this['id_estab_usuario'];

        return Estab_usuario::from('estabs_usuarios as cu')
            ->select(
                'cl.id_estab',
                'cl.bd_name',
                'cl.bd_pass',
                'cl.bd_user',
            )
            ->join('estabs_usuarios_rol as cur', 'cu.id_estab_usuario_rol_activo', '=', 'cur.id_estab_usuario_rol')
            ->join('estabs as cl', 'cur.id_estab', '=', 'cl.id_estab')
            ->where('cu.id_estab_usuario', $id_estab_usuario)
            ->where('cur.estado', 'activo')
            ->where('cl.estado', 'activo')
            ->first();
    }

    public function getEstabsUsuarioAttribute()
    {
        return $this->getEstabsUsuario() ?? null;
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

    public function getEstabUsuarioRolAttribute()
    {
        return $this->getEstabUsuarioRol() ?? null;
    }

    public function getEstabUsuarioRol()
    {
        $id_estab_usuario = $this['id_estab_usuario'];

        return  Estab_usuario::from('estabs_usuarios as cu')
            ->select(
                'cr.id_estab_rol as id_rol',
                'cr.nombre as nombre_rol',
                'cr.abreviatura',

                'cl.nombre_estab',
            )
            ->join('estabs_usuarios_rol as cur', 'cu.id_estab_usuario_rol_activo', '=', 'cur.id_estab_usuario_rol')
            ->join('estabs_roles as cr', 'cur.id_rol', '=', 'cr.id_estab_rol')
            ->join('estabs as cl', 'cl.id_estab', '=', 'cur.id_estab')
            ->where('cu.id_estab_usuario', $id_estab_usuario)
            ->where('cur.estado', 'activo')
            ->first();
    }
}
