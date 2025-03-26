<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionIndicador extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
    protected $table = "evaluaciones_indicadores";
    protected $fillable = [
        'idObjetivo',
        'tipoObjetivo',
        'idIndicador',
        'tipoIndicador',
        'idEvaluacion',
    ];

    // Relación inversa con Evaluacion
    public function evaluacion()
    {
        return $this->belongsTo(Evaluacion::class, 'idEvaluacion', 'id');
    }

    public function objetivos()
    {
        return $this->belongsTo(Objetivo::class, 'idObjetivo', 'id');
    }
}
