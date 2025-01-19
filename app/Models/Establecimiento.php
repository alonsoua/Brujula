<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Establecimiento extends Model
{
    use HasFactory;
    protected $connection = 'cliente';
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

    public static function getAll($idEstablecimiento) {
        $establecimientos = Establecimiento::select('establecimientos.*', 'periodos.nombre as nombrePeriodo')
            ->leftJoin("periodos","establecimientos.idPeriodoActivo","=","periodos.id");

        if (!is_null($idEstablecimiento)) {
            $establecimientos = $establecimientos->where('establecimientos.id', $idEstablecimiento);
        }
        $establecimientos = $establecimientos->orderBy('nombre')
            ->get();

        return $establecimientos;
    }

    public static function getAllActivos($idEstablecimiento) {
        $establecimientos = Establecimiento::select('establecimientos.*', 'periodos.nombre as nombrePeriodo')
            ->leftJoin("periodos","establecimientos.idPeriodoActivo","=","periodos.id")
            ->where('estado', 'Activo');

        if (!is_null($idEstablecimiento)) {
            $establecimientos = $establecimientos->where('establecimientos.id', $idEstablecimiento);
        }
        $establecimientos = $establecimientos->orderBy('nombre')
            ->get();

        return $establecimientos;
    }

    public static function getActivos() {

        return Establecimiento::where('estado', 'Activo')
                        ->orderBy('nombre')
                        ->get();

    }

    public static function getIDPeriodoActivo($idEstablecimiento) {
        return Establecimiento::where('id', $idEstablecimiento)->value('idPeriodoActivo');
    }

}
