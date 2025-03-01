<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class IndicadoresPersonalizados extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
    protected $table = "indicadores_personalizados";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime:d-m-Y H:i:s',
        'updated_at' => 'datetime:d-m-Y H:i:s',
    ];


    protected $fillable = [
        'nombre',
        'tipo',
        'idObjetivo',
        'tipo_objetivo',
        'idCurso',
        'idPeriodo',
        'estado',
        'idUsuario_created',
        'idUsuario_updated',
        'created_at',
        'updated_at',
    ];

    public static function getIndicadoresPersonalizados($idObjetivo) {
        $sql = 'SELECT
                    ind.id as idIndicador
                    , ind.nombre
                FROM indicadores_personalizados as ind
                WHERE
                    ind.idObjetivo = '.$idObjetivo.'
                    AND ind.estado = "Activo"
                Order By ind.id';

        return DB::select($sql, []);
    }
    public static function getIndicadoresobjetivo($idObjetivo) {
        // Optimización usando el constructor de consultas de Laravel
        return self::select('id', 'nombre')
            ->where('idObjetivo', $idObjetivo)
            ->where('estado', 'Activo')
            ->orderBy('id')
            ->get();
    }
}
