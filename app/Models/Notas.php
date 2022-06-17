<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notas extends Model
{
    use HasFactory;

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
    ];

    public static function getNotaObjetivo($idAlumno, $idCurso, $idPeriodo, $idAsignatura, $idObjetivo ) {
        return Notas::select('id')
            ->where('idAlumno', $idAlumno)
            ->where('idPeriodo', $idPeriodo)
            ->where('idCurso', $idCurso)
            ->where('idAsignatura', $idAsignatura)
            ->where('idObjetivo', $idObjetivo)
            ->get();
    }

    public static function getNotasAsignatura($idPeriodo,$idCurso, $idAsignatura) {
        return Notas::select('*')
            ->where('idPeriodo', $idPeriodo)
            ->where('idCurso', $idCurso)
            ->where('idAsignatura', $idAsignatura)
            ->get();
    }

    public static function getAllNotasCurso($idPeriodo,$idCurso) {
        return Notas::select('*')
            ->where('idPeriodo', $idPeriodo)
            ->where('idCurso', $idCurso)
            ->get();
    }
}
