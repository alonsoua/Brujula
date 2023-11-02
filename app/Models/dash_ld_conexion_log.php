<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class dash_ld_conexion_log extends Model
{
    use HasFactory;

    protected $table = "dash_ld_conexion_logs";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'state',
        'message',
        'nombreCurso',
        'idLdConexion',
    ];
}
