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
        'idPeriodo',
        'idEstablecimiento',
        'estado',
    ];

    public static function getNotasConversion($cantidadIndicadores, $puntajeObtenido, $idPeriodo, $idEstablecimiento)
    {
        return NotasConversion::select('nota')
            ->where('cantidadIndicadores', $cantidadIndicadores)
            ->where('puntajeObtenido', $puntajeObtenido)
            ->where('idPeriodo', $idPeriodo)
            ->where('idEstablecimiento', $idEstablecimiento)
            ->first();
    }
}
