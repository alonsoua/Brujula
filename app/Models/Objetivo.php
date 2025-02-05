<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class Objetivo extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
    protected $table = "objetivos";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'abreviatura',
        'priorizacion',
        'priorizacionInterna',
        'idEje',
        'idUnidad',
        'estado',
    ];

    public function eje()
    {
        return $this->belongsTo(Eje::class, 'idEje', 'id'); // Un objetivo pertenece a un eje
    }

    public function indicadores()
    {
        return $this->hasMany(Indicador::class, 'idObjetivo');
    }

    // public function unidad()
    // {
    //     return $this->belongsTo(Unidad::class, 'idUnidad', 'id'); // Un objetivo pertenece a un eje
    // }

    // public static function getObjetivosAsignatura($idAsignatura)
    // {
    //     $sql = 'SELECT
    //                 ob.id
    //                 , ob.nombre as nombreObjetivo
    //                 , un.nombre as nombreUnidad
    //                 , ob.abreviatura
    //                 , ob.priorizacion
    //                 , ob.estado
    //                 , ob.idEje
    //             FROM unidades as un
    //             LEFT JOIN objetivos as ob
    //                 ON ob.idUnidad = un.id
    //             WHERE
    //                 un.idAsignatura = ' . $idAsignatura . '
    //             Order By ob.abreviatura';

    //     return DB::select($sql, []);
    // }

    // public static function getObjetivosActivosAsignatura($idAsignatura, $idPeriodo)
    // {

    //     $sql = 'SELECT
    //                 ob.id
    //                 , ob.nombre as nombreObjetivo
    //                 , ej.nombre as nombreEje
    //                 -- , un.nombre as nombreUnidad
    //                 , ob.abreviatura
    //                 , ob.priorizacion
    //                 , ob.priorizacionInterna
    //                 , ob.estado
    //                 , ob.idEje
    //             -- FROM unidades as un
    //             -- FROM objetivos as ob
    //             -- LEFT JOIN unidades as un
    //             --     ON ob.idUnidad = un.id
    //             FROM ejes as ej
    //             LEFT JOIN objetivos as ob
    //                 ON ob.idEje = ej.id
    //             WHERE
    //                 -- un.idAsignatura = ' . $idAsignatura . '
    //                 ej.idAsignatura = ' . $idAsignatura . '
    //                 AND ob.estado = "Activo"
    //             Order By ob.abreviatura';

    //     return DB::select($sql, []);
    // }


    // * 
    // public static function getObjetivosTrabajados($idsObjetivos, $idAsignatura, $idPeriodo, $idCurso)
    // {
    //     return PuntajeIndicador::selectRaw('
    //         idIndicador, COUNT(id) as puntajes_indicadores
    //     ')
    //     ->whereIn('idIndicador', function ($query) use ($idsObjetivos) {
    //         $query->select('id')->from('indicadores')
    //         ->whereIn('idObjetivo', $idsObjetivos);
    //     })
    //         ->where('idAsignatura', $idAsignatura)
    //         ->where('idCurso', $idCurso)
    //         ->where('idPeriodo', $idPeriodo)
    //         ->where('puntaje', '!=', 0)
    //         ->where('tipoIndicador', 'Normal')
    //         ->groupBy('idIndicador')
    //         ->get();
    // }

    // public static function getObjetivosTrabajadosPersonalizados($idsObjetivos, $idAsignatura, $idPeriodo, $idCurso)
    // {
    //     return PuntajeIndicador::selectRaw('
    //         idIndicador, COUNT(id) as puntajes_indicadores
    //     ')
    //     ->whereIn('idIndicador', function ($query) use ($idsObjetivos) {
    //         $query->select('id')->from('indicadores_personalizados')
    //         ->whereIn('idObjetivo', $idsObjetivos);
    //     })
    //         ->where('idAsignatura', $idAsignatura)
    //         ->where('idCurso', $idCurso)
    //         ->where('idPeriodo', $idPeriodo)
    //         ->where('puntaje', '!=', 0)
    //         ->where('tipoIndicador', 'Personalizado')
    //         ->groupBy('idIndicador')
    //         ->get();
    // }


    public static function getObjetivosTrabajados($idsObjetivos, $idAsignatura, $idPeriodo, $idCurso)
    {
        return Objetivo::whereIn('id', $idsObjetivos)
            ->whereHas('indicadores.puntajesIndicadores', function ($query) use ($idAsignatura, $idPeriodo, $idCurso) {
                $query->where('idAsignatura', $idAsignatura)
                    ->where('idCurso', $idCurso)
                    ->where('idPeriodo', $idPeriodo)
                    ->where('puntaje', '!=', 0) // 🔹 Solo traer puntajes válidos
                    ->where('tipoIndicador', 'Normal');
            })
            ->get(['id']);
    }

    public static function getObjetivosTrabajadosPersonalizados($idsObjetivos, $idAsignatura, $idPeriodo, $idCurso)
    {
        return ObjetivoPersonalizado::whereIn('id', $idsObjetivos)
            ->whereHas('indicadoresPersonalizados.puntajesIndicadores', function ($query) use ($idAsignatura, $idPeriodo, $idCurso) {
                $query->where('idAsignatura', $idAsignatura)
                    ->where('idCurso', $idCurso)
                    ->where('idPeriodo', $idPeriodo)
                    ->where('puntaje', '!=', 0) // 🔹 Solo traer puntajes válidos
                    ->where('tipoIndicador', 'Personalizado');
            })
            ->get(['id']);
    }

    // public static function getObjetivosBetwen($idCursoInicio, $idCursoFin)
    // {
    //     $sql = 'SELECT
    //             o.id
    //             , o.nombre as nombreObjetivo
    //             , e.nombre as nombreEje
    //             , a.nombre as nombreAsignatura
    //             , g.nombre as nombreCurso
    //             , o.abreviatura
    //             , o.priorizacion
    //         FROM cursos as c
    //         LEFT JOIN asignaturas as a
    //             ON c.idGrado = a.idGrado
    //         LEFT JOIN ejes as e
    //             ON a.id = e.idAsignatura
    //         LEFT JOIN objetivos as o
    //             ON e.id = o.idEje
    //         LEFT JOIN grados as g
    //             ON c.idGrado = g.id
    //         WHERE
    //             c.id >= ' . $idCursoInicio . ' AND
    //             c.id <= ' . $idCursoFin . '
    //         Order By o.id';

    //     return DB::select($sql, []);
    // }

    public static function getObjetivosMinisterio()
    {
        $objetivos = Objetivo::with([
            'eje', // Carga el eje relacionado
            'eje.asignatura', // Carga la asignatura a través del eje
            'eje.asignatura.grado', // Carga el grado a través de la asignatura
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
                'priorizacion' => $obj->priorizacion,
                'priorizacionInterna' => $obj->priorizacionInterna,
                'estado' => $obj->estado,
            ];
        });

    }
}
