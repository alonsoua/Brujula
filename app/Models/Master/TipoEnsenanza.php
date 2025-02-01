<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoEnsenanza extends Model
{
    use HasFactory;
    protected $connection = 'master';
    protected $table = "tipo_enseñanzas";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'idNivel',
        'nombre',
        'codigo',
        'estado',
    ];

    public static function getAll()
    {
        return TipoEnsenanza::select(
            'id',
            'idNivel',
            'nombre',
            'codigo',
        )
            ->where('estado', 'Activo')
            ->get();
    }
}
