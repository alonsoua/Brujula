<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoEnsenanza extends Model
{
    use HasFactory;
    protected $connection = 'cliente';
    protected $table = "tipo_enseñanza";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'idNivel',
        'nombre',
        'codigo',
    ];

    public static function getAll() {
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
