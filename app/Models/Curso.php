<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use HasFactory;

    protected $table = "cursos";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'letra',
        'idGrado',
        'idProfesorJefe',
        'idEstablecimiento',
        'idPeriodo',
        'estado',
    ];

    public static function getAll($idEstablecimiento, $idPeriodo) {
        $cursos = Curso::select(
                  'cursos.*'
                , 'users.nombres as nombreProfesorJefe'
                , 'grados.nombre as nombreGrado'
                , 'grados.id as idGrado'
                , 'periodos.nombre as nombrePeriodo'
                , 'establecimientos.nombre as nombreEstablecimiento'
                )
            ->leftJoin("users", "cursos.idProfesorJefe", "=", "users.id")
            ->leftJoin("grados", "cursos.idGrado", "=", "grados.id")
            ->leftJoin("periodos", "cursos.idPeriodo", "=", "periodos.id")
            ->leftJoin("establecimientos", "cursos.idEstablecimiento", "=", "establecimientos.id")
            ->where('cursos.estado', 'Activo');
        if (!is_null($idEstablecimiento)) {
            $cursos = $cursos ->where('establecimientos.id', $idEstablecimiento);
        }
        if (!is_null($idPeriodo)) {
            $cursos = $cursos ->where('cursos.idPeriodo', $idPeriodo);
        }
            $cursos = $cursos->orderBy('cursos.id')
            ->get();
        return $cursos;
    }

    public static function getAllEstado($idEstablecimiento, $estado) {
        $cursos = Curso::select(
                  'cursos.*'
                , 'users.nombres as nombreProfesorJefe'
                , 'grados.nombre as nombreGrado'
                , 'grados.id as idGrado'
                , 'periodos.nombre as nombrePeriodo'
                , 'establecimientos.nombre as nombreEstablecimiento'
                )
            ->leftJoin("users", "cursos.idProfesorJefe", "=", "users.id")
            ->leftJoin("grados", "cursos.idGrado", "=", "grados.id")
            ->leftJoin("periodos", "cursos.idPeriodo", "=", "periodos.id")
            ->leftJoin("establecimientos", "cursos.idEstablecimiento", "=", "establecimientos.id");
        if (!is_null($idEstablecimiento)) {
            $cursos = $cursos ->where('establecimientos.id', $idEstablecimiento);
        }
        if (!is_null($estado)) {
            $cursos = $cursos ->where('cursos.estado', $estado);
        }
            $cursos = $cursos->orderBy('cursos.id')
            ->get();
        return $cursos;
    }

}
