<?php

namespace App\Models;

use App\Models\Master\Asignatura;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsuarioAsignatura extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
    protected $table = "usuario_asignaturas";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'idEstabUsuarioRol',
        'idCurso',
        'idAsignatura',
    ];

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'idCurso', 'id');
    }

    public function asignatura()
    {
        return $this->belongsTo(Asignatura::class, 'idAsignatura', 'id');
    }

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

    public static function getCursoActivo($idUsuarioEstablecimiento, $idPeriodo) {
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
            ->where('cursos.idPeriodo', $idPeriodo)
            ->orderBy('cursos.idGrado')
            ->orderBy('cursos.letra')
            ->distinct()
            ->get();
    }

    public static function getAsignaturaActiva($idEstabUsuarioRol, $idPeriodo)
    {
        // Consulta en la base de datos de establecimiento
        return $usuarioAsignaturas = UsuarioAsignatura::select(
            'usuario_asignaturas.idAsignatura',
            'cursos.id as idCurso',
            'cursos.idGrado'
            )
            ->leftJoin("cursos", "cursos.id", "=", "usuario_asignaturas.idCurso")
            ->where('usuario_asignaturas.idEstabUsuarioRol', $idEstabUsuarioRol)
            ->where('cursos.estado', 'Activo')
            ->where('cursos.idPeriodo', $idPeriodo)
            ->distinct()
            ->get();

        // Obtener los IDs de asignaturas para consultar en la base de datos master
        $idsAsignaturas = $usuarioAsignaturas->pluck('idAsignatura')->unique()->toArray();

        // Consulta en la base de datos master para obtener información de asignaturas
        $asignaturas = \App\Models\Master\Asignatura::select(
            'id',
            'nombre',
            'idGrado'
        )
            ->whereIn('id', $idsAsignaturas)
            ->where('estado', 'Activo')
            ->get()
            ->keyBy('id');

        // Combinar los resultados
        return $usuarioAsignaturas->map(function ($item) use ($asignaturas) {
            $asignatura = $asignaturas->get($item->idAsignatura);
            if (!$asignatura) return null;

            return [
                'id' => $asignatura->id,
                'nombre' => $asignatura->nombre,
                'idGrado' => $asignatura->idGrado,
                'idCurso' => $item->idCurso
            ];
        })->filter()->values();
    }

    public static function getCursoEstablecimientoActivo($idEstablecimiento, $idPeriodoActivo) {
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
            ->where('cursos.idEstablecimiento', $idEstablecimiento)
            ->where('cursos.idPeriodo', $idPeriodoActivo)
            ->where('cursos.estado', 'Activo')
            ->orderBy('cursos.idGrado')
            ->orderBy('cursos.letra')
            ->distinct()
            ->get();
    }

    public static function getAsignaturaCursoActiva() {
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
            ->where('cursos.estado', 'Activo')
            ->where('asignaturas.estado', 'Activo')
            ->orderBy('asignaturas.id')
            ->distinct()
            ->get();
    }

    public static function deleteUsuarioAsignaturas($idEstabUsuarioRol)
    {
        return UsuarioAsignatura::where('idEstabUsuarioRol', $idEstabUsuarioRol)
            ->delete();

    }


}
