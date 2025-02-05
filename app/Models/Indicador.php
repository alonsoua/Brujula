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
        $sql = 'SELECT
                    ind.id
                    , ind.nombre
                FROM indicadores as ind
                WHERE
                    ind.idObjetivo = '.$idObjetivo.'
                    AND ind.estado = "Activo"
                Order By ind.id';

        return DB::select($sql, []);
    }
}
