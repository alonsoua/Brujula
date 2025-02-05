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
    public static function getAjustes($idEstablecimiento, $idPeriodo)
    {
        return Ajuste::select('ajustes.*')
            ->where('ajustes.idEstablecimiento', $idEstablecimiento)
            ->where('ajustes.idPeriodo', $idPeriodo)
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
