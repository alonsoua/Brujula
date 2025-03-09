<?php

namespace App\Models;

use App\Models\Master\Periodo;
use App\Models\Master\Usuario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class IndicadorPersonalizado extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
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
        'tipo',
        'tipo_objetivo',
        'estado',
        'idUsuario_created',
        'idUsuario_updated',
        'created_at',
        'updated_at',
    ];

    public function objetivo()
    {
        return $this->belongsTo(Objetivo::class, 'idObjetivo', 'id');
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'idPeriodo', 'id');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'idCurso', 'id');
    }

    // Relación con el usuario creador desde la conexión master
    public function usuarioCreador()
    {
        return $this->belongsTo(Usuario::class, 'idUsuario_created', 'id');
    }

    // Relación con el usuario editor desde la conexión master
    public function usuarioEditor()
    {
        return $this->belongsTo(Usuario::class, 'idUsuario_updated', 'id');
    }

    public function puntajesIndicadores()
    {
        return $this->hasMany(PuntajeIndicador::class, 'idIndicador');
    }


    public static function getIndicadorPersonalizados($idObjetivo ,$idPeriodo, $idCurso, $tipo) {

        return IndicadorPersonalizado::with([
            'usuarioCreador:id,nombres,primerApellido,segundoApellido,avatar,estado',
            'usuarioEditor:id,nombres,primerApellido,segundoApellido,avatar,estado'
        ])
            ->select(
                'indicador_personalizados.id',
                'indicador_personalizados.nombre',
                'indicador_personalizados.tipo',
                'indicador_personalizados.idUsuario_created',
                'indicador_personalizados.idUsuario_updated',
                'indicador_personalizados.estado',
                'indicador_personalizados.created_at',
                'indicador_personalizados.updated_at'
            )
        ->where('indicador_personalizados.idObjetivo', $idObjetivo)
        ->where('indicador_personalizados.idPeriodo', $idPeriodo)
        ->where('indicador_personalizados.idCurso', $idCurso)
        ->where('indicador_personalizados.tipo_objetivo', $tipo)
        ->where('indicador_personalizados.estado', '!=', 'Eliminado')
        ->get()
        ->map(function ($indicador) {
            return [
                'id' => $indicador->id,
                'nombre' => $indicador->nombre,
                'tipo' => $indicador->tipo,
                'estado' => $indicador->estado,
                'created_at' => $indicador->created_at,
                'updated_at' => $indicador->updated_at,
                'usuarioCreador' => $indicador->usuarioCreador ? [
                    'nombres' => $indicador->usuarioCreador->nombres,
                    'primerApellido' => $indicador->usuarioCreador->primerApellido,
                    'segundoApellido' => $indicador->usuarioCreador->segundoApellido,
                    'avatar' => $indicador->usuarioCreador->avatar,
                    'estado' => $indicador->usuarioCreador->estado,
                ] : null,
                'usuarioEditor' => $indicador->usuarioEditor ? [
                    'nombres' => $indicador->usuarioEditor->nombres,
                    'primerApellido' => $indicador->usuarioEditor->primerApellido,
                    'segundoApellido' => $indicador->usuarioEditor->segundoApellido,
                    'avatar' => $indicador->usuarioEditor->avatar,
                    'estado' => $indicador->usuarioEditor->estado,
                ] : null,
            ];
        });

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
