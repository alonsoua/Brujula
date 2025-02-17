<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EncuestaPregunta extends Model
{
    use HasFactory;

    protected $connection = 'establecimiento';
    protected $table = 'encuesta_preguntas';
    protected $primaryKey = 'id';
    protected $fillable = [
        'numero',
        'titulo',
        'tipo_pregunta',
        'encuesta_id',
        'subcategoria_id',
        'estado'
    ];

    public function encuesta()
    {
        return $this->belongsTo(Encuesta::class, 'encuesta_id');
    }

    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class, 'subcategoria_id');
    }

    public function opciones()
    {
        return $this->hasMany(EncuestaOpcion::class);
    }
}
