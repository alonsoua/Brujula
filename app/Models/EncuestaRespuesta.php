<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EncuestaRespuesta extends Model
{
    use HasFactory;

    protected $connection = 'establecimiento';
    protected $table = 'encuesta_respuestas';
    protected $primaryKey = 'id';
    protected $fillable = [
        'encuesta_opcion_id',
        'texto_respuesta',
        'encuesta_pregunta_id',
        'encuesta_participante_id'
    ];

    public function pregunta()
    {
        return $this->belongsTo(EncuestaPregunta::class, 'encuesta_pregunta_id');
    }

    public function participante()
    {
        return $this->belongsTo(EncuestaParticipante::class, 'encuesta_participante_id');
    }

    public function opcion()
    {
        return $this->belongsTo(EncuestaOpcion::class, 'encuesta_opcion_id');
    }
}
