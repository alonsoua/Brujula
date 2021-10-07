<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Establecimiento extends Model
{
    use HasFactory;

    protected $table = "establecimientos";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'rbd',
        'nombre',
        'insignia',
        'correo',
        'telefono',
        'direccion',
        'dependencia',
        'idPeriodoActivo',
        'estado',
        'created_at',
        'updated_at',
    ];

    public static function getAll() {
        return Establecimiento::select('establecimientos.*', 'periodos.nombre as nombrePeriodo')
            ->leftJoin("periodos","establecimientos.idPeriodoActivo","=","periodos.id")
            ->orderBy('nombre')
            ->get();
    }

    public static function getActivos() {

        return Establecimiento::where('estado', 'Activo')
                        ->orderBy('nombre')
                        ->get();

    }

}
