<?php

namespace App\Models\Master;

use App\Models\Master\Ajuste;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Request;
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

    public function ajustes()
    {
        return $this->hasMany(Ajuste::class, 'idPeriodo', 'id');
    }

    public static function getPeriodosEstablecimiento($idEstablecimiento)
    {
        return self::whereIn('id', function ($query) use ($idEstablecimiento) {
            $query->select('idPeriodo')
            ->from('ajustes')
            ->where('idEstablecimiento', $idEstablecimiento);
        })->orderBy('nombre', 'DESC')->get(['id', 'nombre']);
    }

    public static function getPeriodoActual()
    {
        $annio_actual = date("Y");
        $sql = 'SELECT *
                FROM periodos
                WHERE nombre = ' . $annio_actual;

        return DB::select($sql, []);
    }
}
