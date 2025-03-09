<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Indicador extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
    protected $table = "indicadores";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'idObjetivo',
        'estado',
    ];

    public function objetivo()
    {
        return $this->belongsTo(Objetivo::class, 'idObjetivo', 'id');
    }

    public function puntajesIndicadores()
    {
        return $this->hasMany(PuntajeIndicador::class, 'idIndicador');
    }

    public static function getIndicadoresObjetivo($idObjetivo) {
        // Optimización usando el constructor de consultas de Laravel
        return self::select('id', 'nombre')
            ->where('idObjetivo', $idObjetivo)
            ->where('estado', 'Activo')
            ->orderBy('id')
            ->get();
    }
}
