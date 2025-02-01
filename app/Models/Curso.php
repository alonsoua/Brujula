<?php

namespace App\Models;

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
        'letra',
        'idGrado',
        'idProfesorJefe',
        'idEstablecimiento',
        'idPeriodo',
        'estado',
    ];

    public static function getCurso($idCurso)
    {
        return Curso::select(
            'grados.nombre',
            'cursos.letra',
            'cursos.idProfesorJefe'
        )
            ->leftJoin("grados", "cursos.idGrado", "=", "grados.id")
            ->where('cursos.id', $idCurso)
            ->where('cursos.estado', 'Activo')
            ->first();
    }

    public static function getAll($idPeriodo)
    {
        // Obtener cursos desde la conexión 'establecimiento' (ya configurada en el modelo)
        $cursos = Curso::where('estado', 'Activo')
        ->where('idPeriodo', $idPeriodo)
            ->orderBy('idGrado')
            ->get();

        // Obtener IDs necesarios para las relaciones
        $idUsuarios = $cursos->pluck('idProfesorJefe')->unique()->filter();
        $idGrados = $cursos->pluck('idGrado')->unique()->filter();

        // Obtener datos relacionados desde la conexión 'master'
        $usuarios = Usuario::whereIn('id', $idUsuarios)->pluck('nombres', 'id');
        $grados = Grado::whereIn('id', $idGrados)->pluck('nombre', 'id');

        // Mapear los datos al resultado final
        return $cursos->map(function ($curso) use ($usuarios, $grados) {
            return [
                'id' => $curso->id,
                'nombreProfesorJefe' => $usuarios[$curso->idProfesorJefe] ?? null,
                'nombreGrado' => $grados[$curso->idGrado] ?? null,
                'idGrado' => $curso->idGrado,
                'estado' => $curso->estado,
            ];
        });
    }

    public static function getAllEstado($idEstablecimiento, $estado, $idPeriodo)
    {
        $cursos = Curso::select(
            'cursos.*',
            'users.nombres as nombreProfesorJefe',
            'grados.nombre as nombreGrado',
            'grados.id as idGrado',
            'periodos.nombre as nombrePeriodo',
            'establecimientos.nombre as nombreEstablecimiento'
        )
            ->leftJoin("users", "cursos.idProfesorJefe", "=", "users.id")
            ->leftJoin("grados", "cursos.idGrado", "=", "grados.id")
            ->leftJoin("periodos", "cursos.idPeriodo", "=", "periodos.id")
            ->leftJoin("establecimientos", "cursos.idEstablecimiento", "=", "establecimientos.id");
        if (!is_null($idEstablecimiento)) {
            $cursos = $cursos->where('establecimientos.id', $idEstablecimiento);
        }
        if (!is_null($estado)) {
            $cursos = $cursos->where('cursos.estado', $estado);
        }
        if (!is_null($idPeriodo)) {
            $cursos = $cursos->where('cursos.idPeriodo', $idPeriodo);
        }
        $cursos = $cursos->orderBy('cursos.idGrado')
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
