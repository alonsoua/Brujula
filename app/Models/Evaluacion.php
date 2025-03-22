<?php

namespace App\Models;

use App\Models\Master\Asignatura;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
    protected $table = "evaluaciones";
    protected $fillable = [
        'nombre',
        'fecha',
        'idCurso',
        'idAsignatura',
        'idEstabUsuarioRol',
        'estado',
    ];

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'idCurso', 'id');
    }

    public function asignatura()
    {
        return $this->belongsTo(Asignatura::class, 'idAsignatura', 'id');
    }

    // Relación con EvaluacionIndicador
    public function evaluacionesIndicadores()
    {
        return $this->hasMany(EvaluacionIndicador::class, 'idEvaluacion', 'id');
    }

    // Relación con EvaluacionNota
    public function evaluacionesNotas()
    {
        return $this->hasMany(EvaluacionNota::class, 'idEvaluacion', 'id');
    }
}
