<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Notas extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
    protected $table = "notas";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'nota',
        'idAlumno',
        'idPeriodo',
        'idCurso',
        'idAsignatura',
        'idObjetivo',
        'tipoObjetivo',
    ];

    public static function getNotaObjetivo($idAlumno, $idCurso, $idPeriodo, $idAsignatura, $idObjetivo)
    {
        $notasObjetivos = Notas::select('notas.id')
            ->leftJoin("objetivos", "objetivos.id", "=", "notas.idObjetivo")
            ->where('notas.idAlumno', $idAlumno)
            ->where('notas.idPeriodo', $idPeriodo)
            ->where('notas.idCurso', $idCurso)
            ->where('notas.idAsignatura', $idAsignatura)
            ->where('notas.idObjetivo', $idObjetivo)
            ->where('notas.tipoObjetivo', 'Ministerio')
            ->orderBy('objetivos.abreviatura')
            ->get();

        $notasObjetivosPersonalizados = Notas::select('notas.id')
            ->leftJoin("objetivos_personalizados", "objetivos_personalizados.id", "=", "notas.idObjetivo")
            ->where('notas.idAlumno', $idAlumno)
            ->where('notas.idPeriodo', $idPeriodo)
            ->where('notas.idCurso', $idCurso)
            ->where('notas.idAsignatura', $idAsignatura)
            ->where('notas.idObjetivo', $idObjetivo)
            ->where('notas.tipoObjetivo', 'Interno')
            ->orderBy('objetivos_personalizados.abreviatura')
            ->get();

        $notas = array();
        foreach ($notasObjetivos as $key => $nota) {
            array_push($notas, $nota);
        }

        foreach ($notasObjetivosPersonalizados as $key => $nota) {
            array_push($notas, $nota);
        }
        return $notas;
    }

    public static function getNotasAlumno($idPeriodo, $idCurso, $idAlumno)
    {
        $notas = Notas::select(
            'notas.id',
            'notas.nota',
            'notas.idAsignatura',
            'notas.idObjetivo',
            DB::raw("CASE WHEN notas.tipoObjetivo = 'Ministerio' THEN objetivos.abreviatura ELSE objetivos_personalizados.abreviatura END as abreviatura")
        )
            ->leftJoin('objetivos', function ($join) {
                $join->on('objetivos.id', '=', 'notas.idObjetivo')
                ->where('notas.tipoObjetivo', '=', 'Ministerio');
            })
            ->leftJoin('objetivos_personalizados', function ($join) {
                $join->on('objetivos_personalizados.id', '=', 'notas.idObjetivo')
                ->where('notas.tipoObjetivo', '=', 'Interno');
            })
            ->where('notas.idPeriodo', $idPeriodo)
            ->where('notas.idCurso', $idCurso)
            ->where('notas.idAlumno', $idAlumno)
        ->orderBy('abreviatura')
        ->get();

        return $notas;
    }
}
