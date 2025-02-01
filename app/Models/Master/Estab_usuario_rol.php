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
        'idUsuario',
        'idRol',
        'idEstablecimiento',
        'estado',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'idUsuario', 'id');
    }

    public function rol()
    {
        return $this->belongsTo(MasterRol::class, 'idRol', 'id');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'idEstablecimiento', 'id');
    }

    public static function getRolActivo($idUsuario)
    {
        return Estab_usuario_rol::with('establecimiento')
        ->where('idUsuario', $idUsuario)
            ->where('estado', 1)
            ->first();
    }
}