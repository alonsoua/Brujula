<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class model_has_roles extends Model
{
    use HasFactory;

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

    public static function getRolByModel_id($model_id)
    {
        return model_has_roles::select(
              'roles.id'
            , 'roles.name as nombre'
        )
        ->leftJoin("roles","model_has_roles.role_id", "=", "roles.id")
        ->where('model_has_roles.model_id', $model_id)
        ->get();

    }
}
