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

    public static function getAll($idEstablecimiento) {
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

}
