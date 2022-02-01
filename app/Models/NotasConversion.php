<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotasConversion extends Model
{
    use HasFactory;

    protected $table = "notas_conversion";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cantidadIndicadores',
        'puntajeObtenido',
        'nota',
    ];

    public static function getNotasConversion($cantidadIndicadores, $puntajeObtenido) {
        return NotasConversion::select('nota')
            ->where('cantidadIndicadores', $cantidadIndicadores)
            ->where('puntajeObtenido', $puntajeObtenido)
            ->get();
    }
}
