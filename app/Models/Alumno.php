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
        'estado',
        'idDiagnostico',
        'idPrioritario',
        'idCurso',
        'idEstablecimiento',
    ];

    public static function getAll($idEstablecimiento, $idPeriodo) {
        $alumnos = Alumno::select(
                  'alumnos.*'
                , 'prioritarios.nombre as nombrePrioritario'
                , 'diagnosticos_pie.nombre as nombreDiagnostico'
                , 'diagnosticos_pie.tipoNee as tipoNee'
                , 'cursos.letra'
                , 'grados.nombre as nombreGrado'
                , 'establecimientos.nombre as nombreEstablecimiento'
                )
            ->leftJoin("prioritarios", "alumnos.idPrioritario", "=", "prioritarios.id")
            ->leftJoin("diagnosticos_pie", "alumnos.idDiagnostico", "=", "diagnosticos_pie.id")
            ->leftJoin("cursos", "alumnos.idCurso", "=", "cursos.id")
            ->leftJoin("grados", "cursos.idGrado", "=", "grados.id")
            ->leftJoin("establecimientos", "alumnos.idEstablecimiento", "=", "establecimientos.id");
        if (!is_null($idEstablecimiento)) {
            $alumnos = $alumnos ->where('establecimientos.id', $idEstablecimiento);
        }
        if (!is_null($idPeriodo)) {
            $alumnos = $alumnos ->where('cursos.idPeriodo', $idPeriodo);
        }
        $alumnos = $alumnos->orderBy('establecimientos.id')
        ->orderBy('grados.id')
        ->orderBy('cursos.letra')
        ->orderBy('alumnos.numLista')
        ->get();

        return $alumnos;
    }

    public static function getAlumnosCursoEstablecimiento($idCurso, $idEstablecimiento) {

        return Alumno::where('idCurso', $idCurso)
                        ->where('idEstablecimiento', $idEstablecimiento)
                        ->orderBy('numLista')
                        ->get();

    }

    public static function getAlumnosCurso($idCurso) {

        return Alumno::select(
                'alumnos.*'
                , 'prioritarios.nombre as nombrePrioritario'
                , 'diagnosticos_pie.nombre as nombreDiagnostico'
                , 'diagnosticos_pie.abreviatura as abreviaturaDiagnostico'
                , 'diagnosticos_pie.tipoNee as tipoNee'
            )
            ->leftJoin("prioritarios", "alumnos.idPrioritario", "=", "prioritarios.id")
            ->leftJoin("diagnosticos_pie", "alumnos.idDiagnostico", "=", "diagnosticos_pie.id")
            ->where('alumnos.idCurso', $idCurso)
            ->where('alumnos.estado', 'Activo')
            ->orderBy('alumnos.numLista')
            ->get();

    }


    public static function getAlumno($idAlumno) {

        return Alumno::select(
                'alumnos.*'
            )
            ->where('alumnos.id', $idAlumno)
            ->where('alumnos.estado', 'Activo')
            ->get();

    }

    public static function getAlumnoEstablecimiento($idAlumno) {

        return Alumno::select(
                'establecimientos.*'
            )
            ->leftJoin("establecimientos", "alumnos.idEstablecimiento", "=", "establecimientos.id")
            ->where('alumnos.id', $idAlumno)
            ->where('alumnos.estado', 'Activo')
            ->get();
    }

    public static function getAlumnoCurso($idPeriodo, $idAlumno) {

        return Alumno::select(
                'cursos.id as idCurso',
                'cursos.*',
                'grados.id as idTablaGrados',
                'grados.*'
            )
            ->leftJoin("cursos", "alumnos.idCurso", "=", "cursos.id")
            ->leftJoin("grados", "cursos.idGrado", "=", "grados.id")
            ->where('alumnos.id', $idAlumno)
            // ->where('cursos.idPeriodo', $idPeriodo)
            ->where('alumnos.estado', 'Activo')
            ->get();
    }


    // Alumno::getAlumnoEstablecimiento($idAlumno);
        // $curso = Alumno::($idPeriodo, $idAlumno);



}
