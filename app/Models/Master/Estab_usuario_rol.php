<?php

namespace App\Models\Master;

use App\Models\Master\Rol as MasterRol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estab_usuario_rol extends Model
{
    use HasFactory;

    protected $table = 'estab_usuarios_roles';
    protected $connection = 'master';
    public $timestamps = false;

    protected $fillable = [
        'id_estab_usuario',
        'id_rol',
        'id_estab',
        'estado',
    ];

    public function usuario()
    {
        return $this->belongsTo(Estab_usuario::class, 'id_estab_usuario');
    }

    public function rol()
    {
        return $this->belongsTo(MasterRol::class, 'id_rol', 'id');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Estab::class, 'id_estab', 'id');
    }
}