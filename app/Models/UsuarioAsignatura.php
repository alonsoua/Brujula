<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsuarioAsignatura extends Model
{
    use HasFactory;

    protected $table = "usuario_asignaturas";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'idUsuarioEstablecimiento',
        'idCurso',
        'idAsignatura',
    ];

}
