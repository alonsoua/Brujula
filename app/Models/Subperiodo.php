<?php

namespace App\Models;

use App\Models\Master\Ajuste;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subperiodo extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
    protected $table = 'subperiodos';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'nombre',
        'fecha_inicio',
        'fecha_termino',
        'idAjuste',
        'estado',
    ];

    public function ajuste()
    {
        return $this->belongsTo(Ajuste::class, 'idAjuste');
    }

    public static function getSubperiodos($idAjuste)
    {
        return self::where('idAjuste', $idAjuste)->get();
    }
}
