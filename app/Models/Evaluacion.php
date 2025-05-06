<?php

namespace App\Models;

use App\Models\Master\Asignatura;
use App\Models\Master\Estab_usuario_rol;
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
        'idSubperiodo',
        'estado',
        'fecha_sync',
        'log',
        'id_evaluacion_ld',
        'estado_sync'
    ];

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'idCurso', 'id');
    }

    public function asignatura()
    {
        return $this->belongsTo(Asignatura::class, 'idAsignatura', 'id');
    }

    public function estabUsuarioRol()
    {
        return $this->belongsTo(Estab_usuario_rol::class, 'idEstabUsuarioRol', 'id');
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

    public function subperiodo()
    {
        return $this->belongsTo(Subperiodo::class, 'idSubperiodo', 'id');
    }
}
