<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use HasFactory;

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
        'idPeriodo',
        'idEstablecimiento',
        'estado',
    ];

    public static function getAll() {
        return Curso::select(
                  'cursos.*'
                , 'users.nombres as nombreProfesorJefe'
                , 'grados.nombre as nombreGrado'
                , 'periodos.nombre as nombrePeriodo'
                , 'establecimientos.nombre as nombreEstablecimiento'
                )
            ->leftJoin("users", "cursos.idProfesorJefe", "=", "users.id")
            ->leftJoin("grados", "cursos.idGrado", "=", "grados.id")
            ->leftJoin("periodos", "cursos.idPeriodo", "=", "periodos.id")
            ->leftJoin("establecimientos", "cursos.idEstablecimiento", "=", "establecimientos.id")
            ->orderBy('cursos.id')
            ->get();
    }
}
