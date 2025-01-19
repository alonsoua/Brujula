<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumnos_Cursos extends Model
{
    use HasFactory;
    protected $connection = 'cliente';
    protected $table = "alumnos_cursos";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'idAlumno',
        'idCurso',
        'estado',
    ];
}
