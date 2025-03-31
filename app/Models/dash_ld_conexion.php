<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class dash_ld_conexion extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
    protected $table = "dash_ld_conexions";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $dates = [
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime:d-m-Y H:i:s',
    ];
    protected $fillable = [
        'idPeriodo',
        'idUsuario',
        'created_at',
    ];

    public function logs()
    {
        return $this->hasMany(dash_ld_conexion_log::class, 'idLdConexion', 'id');
    }
}
