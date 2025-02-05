<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ObjetivoPersonalizado extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
    protected $table = "objetivos_personalizados";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'abreviatura',
        'priorizacion',
        'idEje',
        'idUnidad',
        'idEstablecimiento',
        'estado',
    ];

    public function eje()
    {
        return $this->belongsTo(Eje::class, 'idEje', 'id'); // Un objetivo pertenece a un eje
    }

    public function indicadoresPersonalizados()
    {
        return $this->hasMany(IndicadorPersonalizado::class, 'idObjetivo');
    }

    // public static function getObjetivosActivosAsignatura($idAsignatura, $idEstablecimiento) {

    //     $sql = 'SELECT
    //                 ob.id
    //                 , ob.nombre as nombreObjetivo
    //                 , ej.nombre as nombreEje
    //                 -- , un.nombre as nombreUnidad
    //                 , ob.abreviatura
    //                 , ob.priorizacion as priorizacionInterna
    //                 , ob.idEstablecimiento
    //                 , ob.estado
    //                 , ob.idEje
    //             -- FROM unidades as un
    //             -- FROM objetivos as ob
    //             -- LEFT JOIN unidades as un
    //             --     ON ob.idUnidad = un.id
    //             FROM ejes as ej
    //             LEFT JOIN objetivos_personalizados as ob
    //                 ON ob.idEje = ej.id
    //             WHERE
    //                 -- un.idAsignatura = '.$idAsignatura.'
    //                 ej.idAsignatura = '.$idAsignatura.'
    //                 AND ob.idEstablecimiento = '.$idEstablecimiento.'
    //                 AND ob.estado = "Activo"
    //             Order By ob.abreviatura';

    //     return DB::select($sql, []);

    // }

    // $sql = 'SELECT
    //         o.id
    //         , te.id as idNivel
    //         , o.nombre as nombreObjetivo
    //         , e.id as idEje
    //         , e.nombre as nombreEje
    //         , a.id as idAsignatura
    //         , a.nombre as nombreAsignatura
    //         , g.id as idGrado
    //         , g.nombre as nombreCurso
    //         , o.abreviatura
    //         , o.priorizacion as priorizacionInterna
    //         , o.idPeriodo
    //         , o.estado -- autorización
    //         -- , o.priorizaciónInterna -- autorización
    //     FROM objetivos_personalizados as o
    //     LEFT JOIN ejes as e
    //         ON e.id = o.idEje
    //     LEFT JOIN asignaturas as a
    //         ON a.id = e.idAsignatura
    //     LEFT JOIN grados as g
    //         ON g.id = a.idGrado
    //     LEFT JOIN tipo_enseñanza as te
    //         ON te.idNivel = g.idNivel
    //     Where o.idEje != "null" and
    //     o.idPeriodo = ' . $idPeriodo . '
    //     Order By g.id, a.id, e.id, o.abreviatura';

    //     return DB::select($sql, []);
    public static function getObjetivosPersonalizados()
    {


        $objetivos = ObjetivoPersonalizado::with([
            'eje', // Carga el eje relacionado
            'eje.asignatura', // Carga la asignatura a través del eje
            'eje.asignatura.grado' // Carga el grado a través de la asignatura
        ])
            ->whereNotNull('idEje')
            ->orderBy('id')
            ->get();
        $objetivos->loadMissing('eje.asignatura.grado');
        return $objetivos->map(function ($obj) {
            return [
                'id' => $obj->id,
                'nombreObjetivo' => $obj->nombre,
                'tipoObjetivo' => $obj->tipo,
                'idEje' => $obj->eje->id ?? null,
                'nombreEje' => $obj->eje->nombre ?? null,
                'idAsignatura' => $obj->eje->asignatura->id ?? null,
                'nombreAsignatura' => $obj->eje->asignatura->nombre ?? null,
                'idGrado' => $obj->eje->asignatura->grado->id ?? null,
                'nombreGrado' => $obj->eje->asignatura->grado->nombre ?? null,
                'idNivel' => $obj->eje->asignatura->grado->idNivel ?? null,
                'abreviatura' => $obj->abreviatura,
                'priorizacion' => null,
                'priorizacionInterna' => $obj->priorizacion,
                'idPeriodo' => $obj->idPeriodo,
                'estado' => $obj->estado,
            ];
        });
    }
}
