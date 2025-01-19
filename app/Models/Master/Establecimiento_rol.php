<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Establecimiento_rol extends Model
{
    use HasFactory;
    /**
     * Tabla de bd y bd
     */
    protected $table = 'establecimientos_roles';
    protected $connection = 'master';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id_establecimiento_rol';

    /**
     * The "type" of the auto-incrementing ID.
     */
    protected $keyType = 'int';

    public $timestamps = false;


    /**
     * Relación inversa con establecimientos.
     */
    public function establecimientos()
    {
        return $this->belongsToMany(Establecimiento::class, 'establecimientos_usuarios_rol', 'id_rol', 'id_establecimiento')
            ->withPivot('id_establecimiento_usuario_rol', 'id_establecimiento_usuario', 'estado', 'created_at', 'updated_at');
    }
}
