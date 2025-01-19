<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Tabla de bd y bd
     */
    protected $table = 'users';
    protected $connection = 'master';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id_user';

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
        'nombre',
        'correo',
        'password',
        'estado',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Relación con Role.
     */
    public function role()
    {
        return $this->belongsTo(\App\Models\Master\Roles::class, 'id_rol', 'id_rol');
    }

    public function getUsuario()
    {
        $dataUser = User::select(
            'users.*',
            'roles.nombre as nombre_rol',
        )
            ->where('users.id_user', $this['id_user'])
            ->join('roles', 'roles.id_rol', '=', 'users.id_rol')
            ->get()->toArray();
        // return $dataUser;
        if ($dataUser != null) {
            $dataUser[0]['permisos'] = Permisos::select('permisos.action', 'permisos.subject')
                ->where('id_rol', $dataUser[0]['id_rol'])
                ->get()->toArray();

            array_push(
                $dataUser[0]['permisos'],
                array(
                    "action" => "read",
                    "subject" => "home"
                )
            );
        }
        return $dataUser;
    }

    // Accessor
    public function getUsuarioAttribute()
    {
        return $this->getUsuario() ?? null;
    }
}
