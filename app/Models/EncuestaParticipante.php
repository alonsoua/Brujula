<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EncuestaParticipante extends Model
{
    use HasFactory;

    protected $connection = 'establecimiento';
    protected $table = 'encuesta_participantes';
    protected $primaryKey = 'id';
    protected $fillable = ['correo', 'rut', 'verificado', 'usuario_id', 'encuesta_id'];

    public function encuesta()
    {
        return $this->belongsTo(Encuesta::class, 'encuesta_id');
    }
}
