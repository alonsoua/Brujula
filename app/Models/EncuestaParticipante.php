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
    protected $fillable = [
        'rut',
        'nombre',
        'primerApellido',
        'segundoApellido',
        'fecha_inicio',
        'rol_id',
        'curso_id',
        'usuario_id',
        'encuesta_id',
        'estado',
    ];

    // 🔹 Formatear todos los timestamps automáticamente
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('d-m-Y H:i');
    }

    public function encuesta()
    {
        return $this->belongsTo(Encuesta::class, 'encuesta_id');
    }
}
