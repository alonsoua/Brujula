<?php

namespace App\Models\Master;

use App\Models\Curso;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Asignatura extends Model
{
    use HasFactory;
    protected $connection = 'master';
    protected $table = "asignaturas";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'idGrado',
        'estado',
    ];

    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'usuario_asignaturas', 'idAsignatura', 'idCurso')
        ->wherePivot('estado', 'Activo');
    }
    
    public function grado()
    {
        return $this->belongsTo(Grado::class, 'idGrado', 'id');
    }


    public static function getAll()
    {
        $sql = 'SELECT
                    asi.id
                    , asi.nombre as nombreAsignatura
                    , gr.nombre as nombreGrado
                FROM asignaturas as asi
                LEFT JOIN grados as gr
                    ON asi.idGrado = gr.id
                ORDER BY asi.id';

        return DB::select($sql, []);
    }

    public static function getAsignaturasGrado($idGrado)
    {
        $asignaturas = Asignatura::select(
            'asignaturas.id',
            'asignaturas.nombre as nombreAsignatura',
        )
            ->where('asignaturas.idGrado', $idGrado)
            ->where('asignaturas.estado', 'Activo')
            ->orderBy('asignaturas.id')
            ->get();

        return $asignaturas;
    }
}
