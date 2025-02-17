<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EncuestaOpcion extends Model
{
    use HasFactory;

    protected $connection = 'establecimiento';
    protected $table = 'encuesta_opciones';
    protected $primaryKey = 'id';
    protected $fillable = [
        'opcion',
        'texto',
        'imagen',
        'encuesta_pregunta_id',
        'estado'
    ];

    public function pregunta()
    {
        return $this->belongsTo(EncuestaPregunta::class, 'encuesta_pregunta_id');
    }
}
