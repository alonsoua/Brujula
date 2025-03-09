<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumnos_Cursos extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
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

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'idAlumno', 'id');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'idCurso', 'id');
    }
}
