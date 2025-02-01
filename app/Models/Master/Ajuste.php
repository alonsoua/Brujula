<?php

namespace App\Models\Master;

use App\Models\Master\Establecimiento;
use App\Models\Master\Periodo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ajuste extends Model
{
    use HasFactory;
    protected $connection = 'master';
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ajustes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tipo_nota',
        'ld_activo',
        'idEstablecimiento',
        'idPeriodo',
        'fecha_inicio_periodo',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Relationships (optional)
     */
    public static function getAjustes($idEstablecimiento)
    {
        return Ajuste::select('ajustes.*')
            ->leftJoin('establecimientos', 'ajustes.idEstablecimiento', '=', 'establecimientos.id')
            ->where('ajustes.idEstablecimiento', $idEstablecimiento)
            ->whereColumn('ajustes.idPeriodo', 'establecimientos.idPeriodoActivo')
            ->first();
    }

    // Relación con el modelo Establecimiento
    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'idEstablecimiento');
    }

    // Relación con el modelo Periodo
    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'idPeriodo');
    }
}
