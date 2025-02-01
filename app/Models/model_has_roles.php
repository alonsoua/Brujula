<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class model_has_roles extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
    protected $table = "model_has_roles";
    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'role_id',
        'model_type',
        'model_id',
    ];

    public static function getRolByModel_id($model_id, $type)
    {
        if ($type = 'User') {
            $model_type = "App\Models\User";
        }
        if ($type = 'UsuarioEstablecimiento') {
            $model_type = "App\Models\UsuarioEstablecimiento";
        }
        return model_has_roles::select(
              'roles.id'
            , 'roles.name as nombre'
        )
        ->leftJoin("roles","model_has_roles.role_id", "=", "roles.id")
        ->where('model_has_roles.model_id', $model_id)
        ->where('model_has_roles.model_type', $model_type)
        ->get();
    }

    public static function getExisteRolInEstablecimiento($model_id, $nombreRol)
    {
        $model_type = "App\Models\UsuarioEstablecimiento";
        $roles = model_has_roles::select(
            DB::raw('COUNT(roles.id) AS existe')
        )
        ->leftJoin("roles","model_has_roles.role_id", "=", "roles.id")
        ->where('model_has_roles.model_id', $model_id)
        ->where('model_has_roles.model_type', $model_type)
        ->where('roles.name', $nombreRol)
        ->orderBy('roles.id')
        ->get();


        return $roles[0]['existe'] > 0 ? true : false;
        // return $cantidadRoles[0]['roles'];
    }

    public static function getRol($name)
    {
        if ($type = 'User') {
            $model_type = "App\Models\User";
        }
        if ($type = 'UsuarioEstablecimiento') {
            $model_type = "App\Models\UsuarioEstablecimiento";
        }
        return model_has_roles::select(
              'roles.id'
            , 'roles.name as nombre'
            )
        ->where('roles.model_id', $name)
        ->get();
    }

}
