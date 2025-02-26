<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Encuesta extends Model
{
    use HasFactory;

    protected $connection = 'establecimiento';
    protected $table = 'encuestas';
    protected $primaryKey = 'id';
    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo',
        'roles',
        'imagen',
        'slug',
        'estado',
        'usuario_id'
    ];

    protected $casts = [
        'roles' => 'array', // 🔹 Laravel convertirá automáticamente JSON en un array PHP
    ];

    // 🔹 Formatear todos los timestamps automáticamente
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('d-m-Y H:i');
    }

    public function preguntas()
    {
        return $this->hasMany(EncuestaPregunta::class);
    }

    public function participantes()
    {
        return $this->hasMany(EncuestaParticipante::class);
    }
}
