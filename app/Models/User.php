<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements JWTSubject
{
    // use HasApiTokens, HasFactory, Notifiable;
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'email',
        'password',
        'avatar',
        'rut',
        'nombres',
        'primerApellido',
        'segundoApellido',
        'idEstablecimientoActivo',
        'rolActivo',
        'idPeriodoActivo',
        'estado',
        'idUsuarioCreated',
        'idUsuarioUpdated',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Retorna todos los Usuarios
     * Si $idEstablecimiento viene en null, retorna todos los establecimientos
     *
     * @return array
     */
    public static function getAllAdmins() {
        $users = User::select(
                  'users.*'
                , 'establecimientos.nombre as nombreEstablecimiento'
                , 'roles.name as nombreRol'
                )
            ->leftJoin("usuario_establecimientos",
                "users.id",
                "=",
                "usuario_establecimientos.idUsuario"
            )
            ->leftJoin("establecimientos",
                "usuario_establecimientos.idEstablecimiento",
                "=",
                "establecimientos.id"
            )
            ->leftJoin("model_has_roles","users.id", "=", "model_has_roles.model_id")
            ->leftJoin("roles","model_has_roles.role_id", "=", "roles.id")
            ->where(function ($query) {
                $query->where('roles.name', 'Super Administrador')
                      ->orWhere('roles.name', 'Administrador Daem');
            })
            ->where('users.estado', '!=', 'Eliminado')
            ->orderBy('users.nombres')
            ->get();

        return $users;
    }

    /**
     * Retorna todos los Usuarios excepto Super Admin y Admin Daem
     * Si $idEstablecimiento viene en null, retorna todos los establecimientos
     * de lo contrario, filtra por $idEstablecimiento
     *
     * @return array
     */
    public static function getAll($idEstablecimiento) {
        // group_concat(ro.name) as nombreRol
        $sql = 'SELECT
                    us.*
                    , es.nombre as nombreEstablecimiento
                    , ro.name as nombreRol
                FROM users as us
                LEFT JOIN usuario_establecimientos as ue
                    ON us.id = ue.idUsuario
                LEFT JOIN establecimientos as es
                    ON ue.idEstablecimiento = es.id
                LEFT JOIN model_has_roles as mhr
                    ON ue.id = mhr.model_id
                LEFT JOIN roles as ro
                    ON mhr.role_id = ro.id
                WHERE ';

        if (!is_null($idEstablecimiento)) {
            $sql .= "us.estado != 'Eliminado' AND
                ue.idEstablecimiento = ". $idEstablecimiento ." ";
        } else {
            $sql .= "us.estado != 'Eliminado' AND
            ro.name != 'Super Administrador' AND
            ro.name != 'Administrador Daem' ";
        }
        // GROUP BY us.id, es.id
        $sql .= '
            ORDER BY us.nombres asc';

        return DB::select($sql, []);
    }

    /**
     * Retorna todos los Usuarios con Rol Docente, Activos
     * Si $idEstablecimiento viene en null, retorna todos los establecimientos
     *
     * @return array
     */
    public static function getDocentesActivos($idEstablecimiento) {
        $sql = 'SELECT
                    us.id
                    , us.nombres
                    , us.primerApellido
                    , us.segundoApellido
                    , us.avatar
                    , es.id as idEstablecimiento
                    , es.nombre as nombreEstablecimiento
                    , ro.name as nombreRol
                FROM users as us
                LEFT JOIN usuario_establecimientos as ue
                    ON us.id = ue.idUsuario
                LEFT JOIN establecimientos as es
                    ON ue.idEstablecimiento = es.id
                LEFT JOIN model_has_roles as mhr
                    ON ue.id = mhr.model_id
                LEFT JOIN roles as ro
                    ON mhr.role_id = ro.id
                WHERE
                us.estado != "Eliminado" AND
                ro.name = "Docente" OR
                ro.name = "Docente Pie" AND
                us.estado = "Activo" ';
        if (!is_null($idEstablecimiento)) {
            $sql .= 'ue.idEstablecimiento = '. $idEstablecimiento .' ';
        }

        $sql .= '
            ORDER BY us.nombres asc';

        return DB::select($sql, []);

        // $users = User::select(
        //         'users.*'
        //         , 'establecimientos.nombre as nombreEstablecimiento'
        //         , 'establecimientos.id as idEstablecimiento'
        //         , 'roles.name as nombreRol'
        //         )
        //     ->leftJoin("usuario_establecimientos",
        //         "users.id",
        //         "=",
        //         "usuario_establecimientos.idUsuario"
        //     )
        //     ->leftJoin("establecimientos",
        //         "usuario_establecimientos.idEstablecimiento",
        //         "=",
        //         "establecimientos.id"
        //     )
        //     ->leftJoin("model_has_roles",
        //         "users.id", "=", "model_has_roles.model_id"
        //     )
        //     ->leftJoin("roles","model_has_roles.role_id", "=", "roles.id")
        //     ->where('roles.id', 6)
        //     ->where('users.estado', 'Activo');
        // if (!is_null($idEstablecimiento)) {
        //     $users = $users->where(
        //         'usuario_establecimientos.idEstablecimiento',
        //         $idEstablecimiento
        //     );
        // }
        // $users = $users->orderBy('users.nombres')
        //     ->get();

        // return $users;
    }

    public static function getUsuarioEstablecimiento($id_usuario, $id_establecimiento)
    {
        return UsuarioEstablecimiento::where('idUsuario', $id_usuario)
                ->where('idEstablecimiento', $id_establecimiento)
                ->value('id');
    }

}
