<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsuarioAsignatura extends Model
{
    use HasFactory;

    protected $table = "usuario_asignaturas";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'idUsuarioEstablecimiento',
        'idCurso',
        'idAsignatura',
    ];

    public static function getAll($idEstablecimiento, $estado) {
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

    public static function getTipoEnseñanza($idUsuarioEstablecimiento) {
        return UsuarioAsignatura::select(
                'tipo_enseñanza.*'
            )
            ->leftJoin("cursos", "cursos.id", "=", "usuario_asignaturas.idCurso")
            ->leftJoin("grados", "cursos.idGrado", "=", "grados.id")
            ->leftJoin("tipo_enseñanza", "tipo_enseñanza.idNivel", "=", "grados.idNivel")
            ->where('usuario_asignaturas.idUsuarioEstablecimiento', $idUsuarioEstablecimiento)
            ->where('cursos.estado', 'Activo')
            ->orderBy('tipo_enseñanza.id')
            ->distinct()
            ->get();
    }

    public static function getCursoActivo($idUsuarioEstablecimiento) {
        return UsuarioAsignatura::select(
                'cursos.id'
                , 'cursos.letra'
                , 'cursos.idProfesorJefe'
                , 'cursos.idGrado'
                , 'grados.nombre as nombreGrado'
                , 'grados.idNivel'
            )
            ->leftJoin("cursos", "cursos.id", "=", "usuario_asignaturas.idCurso")
            ->leftJoin("grados", "cursos.idGrado", "=", "grados.id")
            ->where('usuario_asignaturas.idUsuarioEstablecimiento', $idUsuarioEstablecimiento)
            ->where('cursos.estado', 'Activo')
            ->orderBy('cursos.idGrado')
            ->orderBy('cursos.letra')
            ->distinct()
            ->get();
    }

    public static function getAsignaturaActiva($idUsuarioEstablecimiento) {
        return UsuarioAsignatura::select(
                'asignaturas.id'
                , 'asignaturas.nombre'
                , 'asignaturas.idGrado'
                , 'cursos.id as idCurso'
            )
            ->leftJoin("cursos", "cursos.id", "=", "usuario_asignaturas.idCurso")
            // ->leftJoin("asignaturas", "asignaturas.idGrado", "=", "cursos.idGrado")
            ->leftJoin('asignaturas', function ($join) {
                $join->on('asignaturas.id', '=', 'usuario_asignaturas.idAsignatura');
                $join->on('asignaturas.idGrado', '=', 'cursos.idGrado');
            })
            // ->leftJoin("grados", "cursos.idGrado", "=", "grados.id")
            ->where('usuario_asignaturas.idUsuarioEstablecimiento', $idUsuarioEstablecimiento)
            ->where('cursos.estado', 'Activo')
            ->orderBy('asignaturas.id')
            ->distinct()
            ->get();
    }

    public static function deleteUsuarioAsignaturas($idUsuarioEstablecimiento) {

        return UsuarioAsignatura::where('idUsuarioEstablecimiento', $idUsuarioEstablecimiento)
            ->delete();

    }


}
