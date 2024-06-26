<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class UsuarioEstablecimiento extends Model
{
    use HasFactory, HasRoles;

    protected $table = "usuario_establecimientos";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'idUsuario',
        'idEstablecimiento',
        'created_at',
        'updated_at',
    ];

    /**
     * Retorna todos los Usuarios
     * Si $idEstablecimiento viene en null, retorna todos los establecimientos
     *
     * @return array
     */
    public static function getEstablecimientosActivosPorUsuario($idUsuario)
    {
        return UsuarioEstablecimiento::select(
            'usuario_establecimientos.id',
            'establecimientos.nombre as nombreEstablecimiento',
            'establecimientos.id as idEstablecimiento',
            'establecimientos.insignia',
            'establecimientos.idPeriodoActivo',
            'periodos.nombre as nombrePeriodo'
        )
            ->leftJoin(
                "establecimientos",
                "usuario_establecimientos.idEstablecimiento",
                "=",
                "establecimientos.id"
            )
            ->leftJoin(
                "periodos",
                "establecimientos.idPeriodoActivo",
                "=",
                "periodos.id"
            )
            ->where('usuario_establecimientos.idUsuario', $idUsuario)
            ->get()
            ->toArray();
    }

    /**
     * Obtiene el id de usuario_establecimientos
     *
     * @return array
     */
    public static function getId($idUsuario, $idEstablecimiento)
    {
        $usuario_establecimiento = UsuarioEstablecimiento::select('id')
            ->where('usuario_establecimientos.idUsuario', $idUsuario)
            ->where('usuario_establecimientos.idEstablecimiento', $idEstablecimiento)
            ->get();
        return $usuario_establecimiento[0]['id'];
    }
}
