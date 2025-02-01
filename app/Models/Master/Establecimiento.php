<?php

namespace App\Models\Master;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Establecimiento extends Model
{
    use HasFactory;
    protected $table = 'establecimientos';
    protected $connection = 'master';

    public $timestamps = false;

    protected $fillable = [
        'bd_name',
        'bd_user',
        'bd_pass',
        'bd_host',
        'bd_port',
        'rbd',
        'nombre',
        'insignia',
        'correo',
        'telefono',
        'direccion',
        'dependencia',
        'idPeriodoActivo',
        'fechaInicioPeriodoActivo',
        'estado',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'bd_name',
        'bd_user',
        'bd_pass',
        'bd_host',
        'bd_port',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function estabUsuarioRoles()
    {
        return $this->hasMany(Estab_usuario_rol::class, 'idEstablecimiento', 'id');
    }

    public function ajustes()
    {
        return $this->hasMany(Ajuste::class, 'idEstablecimiento', 'id');
    }
}
