<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Periodo extends Model
{
    use HasFactory;
    protected $connection = 'master';
    protected $table = "periodos";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'nombre',
        'created_at',
        'updated_at',
    ];

    public static function getPeriodoActual()
    {
        $annio_actual = date("Y");
        $sql = 'SELECT *
                FROM periodos
                WHERE nombre = ' . $annio_actual;

        return DB::select($sql, []);
    }
}
