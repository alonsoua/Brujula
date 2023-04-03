<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Indicador extends Model
{
    use HasFactory;

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
