<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionNota extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
    protected $table = "evaluaciones_notas";
    protected $fillable = [
        'nota',
        'tipoIndicador',
        'idAlumno',
        'idEvaluacion',
        'idUsuarioCreated',
        'idUsuarioUpdated',
    ];

    // Relación inversa con Evaluacion
    public function evaluacion()
    {
        return $this->belongsTo(Evaluacion::class, 'idEvaluacion', 'id');
    }
}
