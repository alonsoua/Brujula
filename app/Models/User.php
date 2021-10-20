<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

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
        'name',
        'email',
        'password',
        'rut',
        'nombres',
        'primerApellido',
        'segundoApellido',
        'idEstablecimientoActivo',
        'rolActivo',
        'idPeriodoActivo',
        'estado',
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
            ->leftJoin("usuario_establecimientos","users.id", "=", "usuario_establecimientos.idUsuario")
            ->leftJoin("establecimientos","usuario_establecimientos.idEstablecimiento", "=", "establecimientos.id")
            ->leftJoin("model_has_roles","users.id", "=", "model_has_roles.model_id")
            ->leftJoin("roles","model_has_roles.role_id", "=", "roles.id")
            ->where(function ($query) {
                $query->where('roles.name', 'Super Administrador')
                      ->orWhere('roles.name', 'Administrador Daem');
            })
            ->orderBy('users.nombres')
            ->get();

        return $users;
    }

    /**
     * Retorna todos los Usuarios
     * Si $idEstablecimiento viene en null, retorna todos los establecimientos
     *
     * @return array
     */
    public static function getAll($idEstablecimiento) {
        $users = User::select(
                  'users.*'
                , 'establecimientos.nombre as nombreEstablecimiento'
                , 'roles.name as nombreRol'
                )
            ->leftJoin("usuario_establecimientos","users.id", "=", "usuario_establecimientos.idUsuario")
            ->leftJoin("establecimientos","usuario_establecimientos.idEstablecimiento", "=", "establecimientos.id")
            ->leftJoin("model_has_roles","usuario_establecimientos.id", "=", "model_has_roles.model_id")
            ->leftJoin("roles","model_has_roles.role_id", "=", "roles.id");

        if (!is_null($idEstablecimiento)) {
            $users = $users->where('usuario_establecimientos.idEstablecimiento', $idEstablecimiento);
        }

        $users = $users->where('roles.name', '!=', 'Super Administrador')
            ->where('roles.name', '!=', 'Administrador Daem')
            ->orderBy('users.nombres')
            ->get();

        return $users;
    }

    /**
     * Retorna todos los Usuarios con Rol Docente, Activos
     * Si $idEstablecimiento viene en null, retorna todos los establecimientos
     *
     * @return array
     */
    public static function getDocentesActivos($idEstablecimiento) {
        $users = User::select(
                'users.*'
                , 'establecimientos.nombre as nombreEstablecimiento'
                , 'establecimientos.id as idEstablecimiento'
                , 'roles.name as nombreRol'
                )
            ->leftJoin("usuario_establecimientos","users.id", "=", "usuario_establecimientos.idUsuario")
            ->leftJoin("establecimientos","usuario_establecimientos.idEstablecimiento", "=", "establecimientos.id")
            ->leftJoin("model_has_roles","users.id", "=", "model_has_roles.model_id")
            ->leftJoin("roles","model_has_roles.role_id", "=", "roles.id")
            ->where('roles.id', 6)
            ->where('users.estado', 'Activo');
        if ($idEstablecimiento !== 'null') {
            $users = $users->where('usuario_establecimientos.idEstablecimiento', $idEstablecimiento);
        }
        $users = $users->orderBy('users.nombres')
            ->get();

        return $users;
    }

}
