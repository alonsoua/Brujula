<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class NotasConversion extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
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
        'estado',
    ];

    public static function getNotasConversion($cantidadIndicadores, $puntajeObtenido, $idPeriodo)
    {
        $notaConversion = NotasConversion::select('nota')
        ->where('cantidadIndicadores', $cantidadIndicadores)
            ->where('puntajeObtenido', $puntajeObtenido)
            ->where('idPeriodo', $idPeriodo)
        ->first();

        // Verificar si no se encontró una configuración de notas
        if (!$notaConversion) {
            // 1️⃣ Registrar advertencia en el log de Laravel
            Log::warning("⚠️ NotasConversion no encontrada para cantidadIndicadores: {$cantidadIndicadores}, puntajeObtenido: {$puntajeObtenido}, idPeriodo: {$idPeriodo}");

            // 2️⃣ Lanzar una excepción para que sea visible en la red del navegador
            throw new Exception("Error: No hay configuración de NotasConversion para los parámetros dados.");
        }

        return $notaConversion;
    }
}
