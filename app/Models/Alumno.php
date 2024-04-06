<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumno extends Model
{
    use HasFactory;

    protected $table = "alumnos";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fechaInscripcion',
        'numMatricula',
        'tipoDocumento',
        'rut',
        'nombres',
        'primerApellido',
        'segundoApellido',
        'correo',
        'genero',
        'fechaNacimiento',
        'paci',
        'pie',
        'numLista',
        'idDiagnostico',
        'idPrioritario',
        'idEstablecimiento',
    ];

    public static function getAll($idEstablecimiento, $idPeriodo = null)
    {
        $alumnos = Alumno::select(
            'alumnos.*',
            'alumnos_cursos.idCurso',
            'alumnos_cursos.estado',
            'prioritarios.nombre as nombrePrioritario',
            'diagnosticos_pie.nombre as nombreDiagnostico',
            'diagnosticos_pie.tipoNee as tipoNee',
            'cursos.letra',
            'grados.nombre as nombreGrado',
            'establecimientos.nombre as nombreEstablecimiento'
        )
            ->leftJoin("prioritarios", "alumnos.idPrioritario", "=", "prioritarios.id")
            ->leftJoin("diagnosticos_pie", "alumnos.idDiagnostico", "=", "diagnosticos_pie.id")
            ->leftJoin("alumnos_cursos", "alumnos.id", "=", "alumnos_cursos.idAlumno")
            ->leftJoin("cursos", "alumnos_cursos.idCurso", "=", "cursos.id")
            ->leftJoin("grados", "cursos.idGrado", "=", "grados.id")
            ->leftJoin("establecimientos", "alumnos.idEstablecimiento", "=", "establecimientos.id");
        if (!is_null($idEstablecimiento)) {
            $alumnos = $alumnos->where('cursos.idEstablecimiento', $idEstablecimiento);
        }
        if (!is_null($idPeriodo)) {
            $alumnos = $alumnos->where('cursos.idPeriodo', $idPeriodo);
        }
        $alumnos = $alumnos->orderBy('establecimientos.id')
            ->orderBy('grados.id')
            ->orderBy('cursos.letra')
            ->orderBy('alumnos.numLista')
            ->get();

        return $alumnos;
    }

    public static function getAlumnosCursoEstablecimiento($idCurso, $idEstablecimiento)
    {

        return Alumno::leftJoin("alumnos_cursos", "alumnos.id", "=", "alumnos_cursos.idAlumno")
            ->leftJoin("cursos", "alumnos_cursos.idCurso", "=", "cursos.id")
            ->where('alumnos_cursos.idCurso', $idCurso)
            ->where('cursos.idEstablecimiento', $idEstablecimiento)
            ->orderBy('numLista')
            ->get();
    }

    public static function getAlumnosCurso($idCurso)
    {

        return Alumno::select(
            'alumnos.*',
            'alumnos_cursos.idCurso',
            'alumnos_cursos.estado',
            'prioritarios.nombre as nombrePrioritario',
            'diagnosticos_pie.nombre as nombreDiagnostico',
            'diagnosticos_pie.abreviatura as abreviaturaDiagnostico',
            'diagnosticos_pie.tipoNee as tipoNee'
        )
            ->leftJoin("prioritarios", "alumnos.idPrioritario", "=", "prioritarios.id")
            ->leftJoin("diagnosticos_pie", "alumnos.idDiagnostico", "=", "diagnosticos_pie.id")
            ->leftJoin("alumnos_cursos", "alumnos.id", "=", "alumnos_cursos.idAlumno")
            ->leftJoin("cursos", "alumnos_cursos.idCurso", "=", "cursos.id")
            ->where('alumnos_cursos.idCurso', $idCurso)
            ->where('alumnos_cursos.estado', 'Activo')
            ->orderBy('alumnos.numLista')
            ->get();
    }


    public static function getAlumno($idAlumno)
    {

        return Alumno::select(
            'alumnos.*',
            'alumnos_cursos.idCurso',
            'alumnos_cursos.estado'

        )
            ->leftJoin("alumnos_cursos", "alumnos.id", "=", "alumnos_cursos.idAlumno")
            ->where('alumnos_cursos.idAlumno', $idAlumno)
            ->where('alumnos_cursos.estado', 'Activo')
            ->get();
    }

    public static function getAlumnoEstablecimiento($idAlumno)
    {

        return Alumno::select(
            'establecimientos.*'
        )
            ->leftJoin("establecimientos", "alumnos.idEstablecimiento", "=", "establecimientos.id")
            ->where('alumnos.id', $idAlumno)
            ->where('alumnos.estado', 'Activo')
            ->get();
    }

    public static function getAlumnoCurso($idPeriodo, $idAlumno)
    {
        return Alumno::select(
            'alumnos_cursos.idCurso',
            'cursos.*',
            'grados.id as idTablaGrados',
            'grados.*'
        )
            ->leftJoin("alumnos_cursos", "alumnos.id", "=", "alumnos_cursos.idAlumno")
            ->leftJoin("cursos", "alumnos_cursos.idCurso", "=", "cursos.id")
            ->leftJoin("grados", "cursos.idGrado", "=", "grados.id")
            ->where('alumnos_cursos.idAlumno', $idAlumno)
            // ->where('cursos.idPeriodo', $idPeriodo)
            ->where('alumnos_cursos.estado', 'Activo')
            ->get();
    }
    // Alumno::getAlumnoEstablecimiento($idAlumno);
    // $curso = Alumno::($idPeriodo, $idAlumno);
}
