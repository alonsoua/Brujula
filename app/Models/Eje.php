<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Eje extends Model
{
    use HasFactory;

    protected $table = "ejes";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'idAsignatura',
        'estado',
    ];

    public static function getEjesPorAsignatura($idAsignatura) {
        // $sql = 'SELECT
        //             id
        //             , nombre
        //         FROM ejes
        //         WHERE
        //             idAsignatura = ';
        // return DB::select($sql, []);

            return Eje::select('ejes.id', 'ejes.nombre')
                ->leftJoin("objetivos", "objetivos.idEje", "=", "ejes.id")
                ->where('ejes.idAsignatura', $idAsignatura)
                ->orderBy('objetivos.abreviatura')
                ->get();


    }
}
