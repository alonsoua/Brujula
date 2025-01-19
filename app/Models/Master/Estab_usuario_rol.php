<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estab_usuario_rol extends Model
{
    use HasFactory;
    /**
     * Tabla de bd y bd
     */
    protected $table = 'estab_usuarios_roles';
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
        'id_estab',
        'id_estab_usuario',
        'id_rol',
        'estado',
    ];
}
