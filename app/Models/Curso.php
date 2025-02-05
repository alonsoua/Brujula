<?php

namespace App\Models;

use App\Models\Master\Asignatura;
use App\Models\Master\Establecimiento;
use App\Models\Master\Grado;
use App\Models\Master\Periodo;
use App\Models\Master\Usuario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
    protected $table = "cursos";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'letra',
        'idGrado',
        'idProfesorJefe',
        'idEstablecimiento',
        'idPeriodo',
        'estado',
    ];

    public function asignaturas()
    {
        return $this->belongsToMany(Asignatura::class, 'usuario_asignaturas', 'idCurso', 'idAsignatura')
        ->wherePivot('estado', 'Activo');
    }
    

    public static function getCurso($idCurso)
    {
        return Curso::select(
            'grados.nombre',
            'cursos.nombre',
            'cursos.letra',
            'cursos.idProfesorJefe'
        )
            ->leftJoin("grados", "cursos.idGrado", "=", "grados.id")
            ->where('cursos.id', $idCurso)
            ->where('cursos.estado', 'Activo')
            ->first();
    }


    // * 
    public static function getAll($idPeriodo)
    {
        // Obtener cursos desde la conexión 'establecimiento' (ya configurada en el modelo)
        $cursos = Curso::where('estado', 'Activo')
        ->where('idPeriodo', $idPeriodo)
            ->orderBy('idGrado')
            ->orderBy('letra') // Asegurando ordenación
        ->get();

        // Obtener IDs necesarios para las relaciones
        $idUsuarios = $cursos->pluck('idProfesorJefe')->unique()->filter();

        // Obtener datos relacionados desde la conexión 'master' en una sola consulta
        $usuarios = Usuario::whereIn('id', $idUsuarios)
        ->get(['id', 'nombres', 'primerApellido', 'segundoApellido'])
        ->keyBy('id'); // Almacena los datos indexados por ID

        // Mapear datos a los cursos sin necesidad de nuevas consultas
        return $cursos->map(function ($curso) use ($usuarios) {
            $profesor = $usuarios->get($curso->idProfesorJefe);
            return [
                'id' => $curso->id,
                'nombre' => $curso->nombre,
                'letra' => $curso->letra,
                'nombreProfesorJefe' => $profesor ? "{$profesor->nombres} {$profesor->primerApellido} {$profesor->segundoApellido}" : null,
                'idGrado' => $curso->idGrado,
                'estado' => $curso->estado,
            ];
        });
    }

    public static function getAllEstado($estado, $idPeriodo)
    {
        $cursos = Curso::select('cursos.*');
        if (!is_null($estado)) {
            $cursos = $cursos->where('cursos.estado', $estado);
        }
        if (!is_null($idPeriodo)) {
            $cursos = $cursos->where('cursos.idPeriodo', $idPeriodo);
        }
        $cursos = $cursos->orderBy('cursos.idGrado')
            ->orderBy('letra')
            ->get();
        return $cursos;
    }

    public static function getCursosAlumno($idPeriodo, $idEstablecimiento, $cod_grado, $tipo_ensenanza, $letra_curso)
    {
        $cursos = Curso::select('cursos.*')
            ->leftJoin("grados", "grados.id", "=", "cursos.idGrado")
            ->where('grados.idGrado', $cod_grado)
            ->where('grados.idNivel', $tipo_ensenanza)
            ->where('cursos.letra', $letra_curso)
            ->where('cursos.idEstablecimiento', $idEstablecimiento)
            ->where('cursos.idPeriodo', $idPeriodo)
            ->where('cursos.estado', 'Activo')
            ->orderBy('cursos.id')
            ->first();
        return $cursos;
    }
}
