<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class Establecimiento_usuario extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Tabla de bd y bd
     */
    protected $table = 'establecimientos_usuarios';
    protected $connection = 'master';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id_establecimiento_usuario';

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
        'password_temporal',
        'nombre',
        'celular',
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
        'password_temporal',
    ];

    /**
     * Relación con Establecimiento.
     */
    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'id_establecimiento');
    }
    /**
     * Relación con Rol.
     */
    public function roles()
    {
        return $this->belongsTo(Establecimiento_rol::class, 'id_establecimiento_rol');
    }

    public function getEstablecimientoBDAttribute()
    {
        return $this->getEstablecimientoBD() ?? null;
    }

    public function getEstablecimientoBD()
    {
        $id_establecimiento_usuario = $this['id_establecimiento_usuario'];

        return Establecimiento_usuario::from('establecimientos_usuarios as cu')
            ->select(
                'cl.id_establecimiento',
                'cl.bd_name',
                'cl.bd_pass',
                'cl.bd_user',
            )
            ->join('establecimientos_usuarios_rol as cur', 'cu.id_establecimiento_usuario_rol_activo', '=', 'cur.id_establecimiento_usuario_rol')
            ->join('establecimientos as cl', 'cur.id_establecimiento', '=', 'cl.id_establecimiento')
            ->where('cu.id_establecimiento_usuario', $id_establecimiento_usuario)
            ->where('cur.estado', 'activo')
            ->where('cl.estado', 'activo')
            ->first();
    }

    public function getEstablecimientosUsuarioAttribute()
    {
        return $this->getEstablecimientosUsuario() ?? null;
    }

    public function getEstablecimientosUsuario()
    {
        $id_establecimiento_usuario = $this['id_establecimiento_usuario'];

        $establecimientosRoles = Establecimiento_usuario_rol::from('establecimientos_usuarios_rol as cur')
            ->select(
                // CLIENTE
                'cl.id_establecimiento',
                'cl.nombre_establecimiento',
                // 'cl.nombre_contacto',
                // 'cl.celular',
                // 'cl.correo',
                // 'cl.logotipo',
                // 'cl.pais',
                // 'cl.codigo_pais',
                // 'cl.moneda',
                // 'cl.timezone',
                // 'cl.verificacion_email',
                // 'cl.tipo_establecimiento',
                // 'cl.created_at',
                // 'cl.updated_at',

                // ROLES
                'cr.id_establecimiento_rol',
                'cr.nombre as nombre_rol',
                'cr.abreviatura',
            )
            ->join('establecimientos as cl', 'cur.id_establecimiento', '=', 'cl.id_establecimiento')
            ->join('establecimientos_roles as cr', 'cur.id_rol', '=', 'cr.id_establecimiento_rol')
            ->where('cur.id_establecimiento_usuario', $id_establecimiento_usuario)

            ->where('cur.estado', 'activo')
            ->where('cl.estado', 'activo')

            ->get();

        $result = [];

        foreach ($establecimientosRoles as $item) {
            if (!isset($result[$item->id_establecimiento])) {
                $result[$item->id_establecimiento] = [
                    'id_establecimiento' => $item->id_establecimiento,

                    'bd_name' => $item->bd_name,
                    'bd_pass' => $item->bd_pass,
                    'bd_user' => $item->bd_user,

                    'nombre_establecimiento' => $item->nombre_establecimiento,
                    'roles' => []
                ];
            }
            $result[$item->id_establecimiento]['roles'][] = [
                'id_rol' => $item->id_establecimiento_rol,
                'nombre' => $item->nombre_rol,
                'abreviatura' => $item->abreviatura
            ];
        }

        return $result = array_values($result);
    }

    public function getEstablecimientoUsuarioRolAttribute()
    {
        return $this->getEstablecimientoUsuarioRol() ?? null;
    }

    public function getEstablecimientoUsuarioRol()
    {
        $id_establecimiento_usuario = $this['id_establecimiento_usuario'];

        return  Establecimiento_usuario::from('establecimientos_usuarios as cu')
            ->select(
                'cr.id_establecimiento_rol as id_rol',
                'cr.nombre as nombre_rol',
                'cr.abreviatura',

                'cl.nombre_establecimiento',
            )
            ->join('establecimientos_usuarios_rol as cur', 'cu.id_establecimiento_usuario_rol_activo', '=', 'cur.id_establecimiento_usuario_rol')
            ->join('establecimientos_roles as cr', 'cur.id_rol', '=', 'cr.id_establecimiento_rol')
            ->join('establecimientos as cl', 'cl.id_establecimiento', '=', 'cur.id_establecimiento')
            ->where('cu.id_establecimiento_usuario', $id_establecimiento_usuario)
            ->where('cur.estado', 'activo')
            ->first();
    }
}
