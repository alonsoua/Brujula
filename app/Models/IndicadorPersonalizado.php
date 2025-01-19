<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class IndicadorPersonalizado extends Model
{
    use HasFactory;
    protected $connection = 'cliente';
    protected $table = "indicador_personalizados";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime:d-m-Y H:i:s',
        'updated_at' => 'datetime:d-m-Y H:i:s',
    ];


    protected $fillable = [
        'nombre',
        'idObjetivo',
        'idPeriodo',
        'idCurso',
        'tipo_objetivo',
        'estado',
        'idUsuario_created',
        'idUsuario_updated',
        'created_at',
        'updated_at',
    ];

    public static function getIndicadorPersonalizados($idObjetivo ,$idPeriodo, $idCurso, $tipo) {

        return IndicadorPersonalizado::selectRaw('
              indicador_personalizados.id
            , indicador_personalizados.nombre
            , indicador_personalizados.idUsuario_created
            , indicador_personalizados.idUsuario_updated
            , indicador_personalizados.estado
            , indicador_personalizados.created_at
            , indicador_personalizados.updated_at
            , ucreated.nombres as nombreCreador
            , ucreated.primerApellido as primerApellidoCreador
            , ucreated.segundoApellido as segundoApellidoCreador
            , ucreated.avatar as avatarCreador
            , ucreated.estado as estadoCreador
            , uupdated.nombres as nombreEditor
            , uupdated.primerApellido as primerApellidoEditor
            , uupdated.segundoApellido as segundoApellidoEditor
            , uupdated.avatar as avatarEditor
            , uupdated.estado as estadoEditor
        ')
        ->leftJoin(DB::raw("(SELECT * FROM users) as ucreated"),function($join) {
            $join->on('indicador_personalizados.idUsuario_created','=','ucreated.id');
        })
        ->leftJoin(DB::raw("(SELECT * FROM users) as uupdated"),function($join2){
            $join2->on('indicador_personalizados.idUsuario_updated','=','uupdated.id');
        })
        ->where('indicador_personalizados.idObjetivo', $idObjetivo)
        ->where('indicador_personalizados.idPeriodo', $idPeriodo)
        ->where('indicador_personalizados.idCurso', $idCurso)
        ->where('indicador_personalizados.tipo_objetivo', $tipo)
        ->where('indicador_personalizados.estado', '!=', 'Eliminado')
        ->get();

    }

    public static function getIndicadorPersonalizadosAprobados($idObjetivo ,$idPeriodo, $idCurso, $tipo) {

        return IndicadorPersonalizado::selectRaw('
              indicador_personalizados.id
            , indicador_personalizados.nombre
            , indicador_personalizados.idUsuario_created
            , indicador_personalizados.idUsuario_updated
            , indicador_personalizados.estado
            , indicador_personalizados.created_at
            , indicador_personalizados.updated_at
        ')
        ->where('indicador_personalizados.idObjetivo', $idObjetivo)
        ->where('indicador_personalizados.idPeriodo', $idPeriodo)
        ->where('indicador_personalizados.idCurso', $idCurso)
        ->where('indicador_personalizados.tipo_objetivo', $tipo)
        ->where('indicador_personalizados.estado', 'Aprobado')
        ->get();

    }
}
