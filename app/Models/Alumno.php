<?php

namespace App\Models;

use App\Models\Master\DiagnosticoPie;
use App\Models\Master\Prioritario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumno extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
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

    public function curso()
    {
        return $this->belongsToMany(Curso::class, 'alumnos_cursos', 'idAlumno', 'idCurso')
        ->withPivot('estado')
        ->wherePivot('estado', 'Activo'); 
    }

    public function prioritario()
    {
        return $this->belongsTo(Prioritario::class, 'idPrioritario', 'id');
    }

    public function diagnostico()
    {
        return $this->belongsTo(DiagnosticoPie::class, 'idDiagnostico', 'id');
    }

    public static function getAll($idPeriodo = null)
    {
        return Alumno::with(['curso' => function ($query) use ($idPeriodo) {
            $query->select(
                'cursos.id',
                'letra',
                'nombre',
                'idPeriodo',
                'idGrado'
            )->where('idPeriodo', $idPeriodo);
        }, 'prioritario:id,nombre', 'diagnostico:id,nombre,tipoNee'])
        ->select('alumnos.*', 'alumnos_cursos.idCurso', 'alumnos_cursos.estado')
        ->leftJoin('alumnos_cursos', 'alumnos.id', '=', 'alumnos_cursos.idAlumno')
        ->leftJoin('cursos', 'alumnos_cursos.idCurso', '=', 'cursos.id') // Asegurar que la tabla cursos esté unida
        ->whereHas('curso', function ($query) use ($idPeriodo) {
            $query->where('idPeriodo', $idPeriodo);
        })
            ->orderBy('cursos.idGrado')
            ->orderBy('cursos.letra')
            ->orderBy('alumnos.numLista')
        ->get();
    }

    public static function getSiguienteNumLista($idCurso)
    {
        return Alumno::leftJoin("alumnos_cursos", "alumnos.id", "=", "alumnos_cursos.idAlumno")
            ->where('alumnos_cursos.idCurso', $idCurso)
            ->max('alumnos.numLista') + 1;
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
            'alumnos.id',
            'alumnos.nombres',
            'alumnos.primerApellido',
            'alumnos.segundoApellido',
            'alumnos_cursos.idCurso'
        )
            ->leftJoin("alumnos_cursos", "alumnos.id", "=", "alumnos_cursos.idAlumno")
            ->where('alumnos_cursos.idAlumno', $idAlumno)
            ->where('alumnos_cursos.estado', 'Activo')
            ->first();
    }

    public static function getAlumnoCurso($idPeriodo, $idAlumno)
    {
        return Alumno::select(
            'cursos.id',
            'cursos.nombre',
            'cursos.letra',
            'cursos.idProfesorJefe',
            'cursos.idGrado',
        )
            ->leftJoin("alumnos_cursos", "alumnos.id", "=", "alumnos_cursos.idAlumno")
            ->leftJoin("cursos", "alumnos_cursos.idCurso", "=", "cursos.id")
            ->where('alumnos_cursos.idAlumno', $idAlumno)
            ->where('cursos.idPeriodo', $idPeriodo)
            ->where('alumnos_cursos.estado', 'Activo')
            ->first();
    }
}
