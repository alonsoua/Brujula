<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicadorPersonalizado extends Model
{
    use HasFactory;

    protected $table = "indicador_personalizados";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'idObjetivo',
        'idPeriodo',
        'estado',
        'idUsuario_created',
        'idUsuario_updated',
        'created_at',
        'updated_at',
    ];

    public static function getIndicadorPersonalizados($idObjetivo ,$idPeriodo) {
        $IndicadorPersonalizado = IndicadorPersonalizado::select('id', 'nombre', 'idUsuario_created', 'idUsuario_updated')
            ->where('idObjetivo', $idObjetivo)
            ->where('idPeriodo', $idPeriodo)
            ->where('estado', 'Activo')
            ->get();

        return $IndicadorPersonalizado;
    }
}
