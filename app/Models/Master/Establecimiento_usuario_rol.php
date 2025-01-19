<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Establecimiento_usuario_rol extends Model
{
    use HasFactory;
    /**
     * Tabla de bd y bd
     */
    protected $table = 'establecimientos_usuarios_rol';
    protected $connection = 'master';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id_establecimiento_usuario_rol';

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
        'id_establecimiento',
        'id_establecimiento_usuario',
        'id_rol',
        'estado',
    ];
}
