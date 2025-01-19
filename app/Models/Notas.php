<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notas extends Model
{
    use HasFactory;
    protected $connection = 'cliente';
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

    public static function getNotasAsignatura($idPeriodo, $idCurso, $idAsignatura)
    {
        // return Notas::select('*')
        //     ->where('idPeriodo', $idPeriodo)
        //     ->where('idCurso', $idCurso)
        //     ->where('idAsignatura', $idAsignatura)
        //     ->get();

        $notasObjetivos = Notas::select('notas.*')
            ->leftJoin("objetivos", "objetivos.id", "=", "notas.idObjetivo")
            ->where('notas.idPeriodo', $idPeriodo)
            ->where('notas.idCurso', $idCurso)
            ->where('notas.idAsignatura', $idAsignatura)
            ->where('notas.tipoObjetivo', 'Ministerio')
            ->orderBy('objetivos.abreviatura')
            ->get();

        $notasObjetivosPersonalizados = Notas::select('notas.*')
            ->leftJoin("objetivos_personalizados", "objetivos_personalizados.id", "=", "notas.idObjetivo")
            ->where('notas.idPeriodo', $idPeriodo)
            ->where('notas.idCurso', $idCurso)
            ->where('notas.idAsignatura', $idAsignatura)
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

    public static function getAllNotasCurso($idPeriodo, $idCurso)
    {
        $notasObjetivos = Notas::select('notas.*')
            ->leftJoin("objetivos", "objetivos.id", "=", "notas.idObjetivo")
            ->where('notas.idPeriodo', $idPeriodo)
            ->where('notas.idCurso', $idCurso)
            ->where('notas.tipoObjetivo', 'Ministerio')
            ->orderBy('objetivos.abreviatura')
            ->get();

        $notasObjetivosPersonalizados = Notas::select('notas.*')
            ->leftJoin("objetivos_personalizados", "objetivos_personalizados.id", "=", "notas.idObjetivo")
            ->where('notas.idPeriodo', $idPeriodo)
            ->where('notas.idCurso', $idCurso)
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
        $notasObjetivos = Notas::select('notas.*')
            ->leftJoin("objetivos", "objetivos.id", "=", "notas.idObjetivo")
            ->where('notas.idPeriodo', $idPeriodo)
            ->where('notas.idCurso', $idCurso)
            ->where('notas.idAlumno', $idAlumno)
            ->where('notas.tipoObjetivo', 'Ministerio')
            ->orderBy('objetivos.abreviatura')
            ->get();

        $notasObjetivosPersonalizados = Notas::select('notas.*')
            ->leftJoin("objetivos_personalizados", "objetivos_personalizados.id", "=", "notas.idObjetivo")
            ->where('notas.idPeriodo', $idPeriodo)
            ->where('notas.idCurso', $idCurso)
            ->where('notas.idAlumno', $idAlumno)
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
}
