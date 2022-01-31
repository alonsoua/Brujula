<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuntajeIndicadorTransformacion extends Model
{
    use HasFactory;

    protected $table = "puntajes_indicadores_transformacion";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'puntaje',
        'rangoDesde',
        'rangoHasta',
        'nivelLogro',
    ];
}
