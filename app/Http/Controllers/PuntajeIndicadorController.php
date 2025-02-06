<?php

namespace App\Http\Controllers;

use App\Models\Master\Ajuste;
use App\Models\PuntajeIndicador;
use App\Models\PuntajeIndicadorTransformacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PuntajeIndicadorController extends Controller
{
    /**
     * @var alumnoController
     */
    protected $alumnoController;

    /**
     * @var notasConversionController
     */
    protected $notasConversionController;

    public function __construct()
    {
        $this->alumnoController = app('App\Http\Controllers\AlumnoController');
        $this->notasConversionController = app('App\Http\Controllers\NotasConversionController');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return PuntajeIndicador::all();
    }

    /**
     * Obtiene los puntajes por indicador de cada alumno
     * * $idPeriodo
     * * $idCurso
     * * $idAsignatura
     * * $idObjetivo
     * * $tipo
     * @return \Illuminate\Http\Response
     */

    public function getPuntajesIndicadores(Request $request, $idCurso, $idAsignatura, $idObjetivo, $tipoObjetivo)
    {
        $user = $request->user()->getUserData();
        $idEstablecimiento = $user['establecimiento']['id'];
        $idPeriodo = $user['periodo']['id'];

        // Asegurar que getAlumnosCurso devuelva una colección
        $alumnos = collect($this->alumnoController->getAlumnosCurso($idCurso));
        $alumnosIds = $alumnos->pluck('id')->toArray();

        // Definir tipo de indicador y relación correcta
        $tipoIndicador = $tipoObjetivo === 'Ministerio' ? 'Normal' : 'Interno';
        $relacionIndicador = $tipoObjetivo === 'Ministerio' ? 'indicador' : 'indicadorPersonalizado';
        // Obtener puntajes de los indicadores en una sola consulta optimizada
        $puntajes = PuntajeIndicador::with([$relacionIndicador => function ($query) use ($idObjetivo) {
            $query->where('idObjetivo', $idObjetivo);
        }])
            ->where('idPeriodo', $idPeriodo)
            ->where('idCurso', $idCurso)
            ->where('idAsignatura', $idAsignatura)
            ->where('tipoIndicador', $tipoIndicador)
            ->where('estado', 'Activo')
            ->where('puntaje', '!=', 0)
            ->whereIn('idAlumno', $alumnosIds)
            ->whereHas($relacionIndicador, function ($query) use ($idObjetivo) {
                $query->where('idObjetivo', $idObjetivo);
            }) // 🔹 Solo traer registros si tienen indicador relacionado
            ->get()
            ->groupBy('idAlumno');

        // Procesar alumnos y calcular promedios
        $alumnosPuntajes = $alumnos->map(function ($alumno) use ($puntajes, $idEstablecimiento, $idPeriodo) {
            $puntajesAlumno = $puntajes[$alumno['id']] ?? collect();
            return [
                'idAlumno' => $alumno['id'],
                'puntajes' => $puntajesAlumno,
                'promedio' => $puntajesAlumno->isNotEmpty()
                    ? $this->getPromedioConversion($puntajesAlumno, $idEstablecimiento, $idPeriodo)
                    : 'undefined',
            ];
        });

        return $alumnosPuntajes;
    }

    /**
     * Obtiene los promedios por alumno de los indicadores con puntaje
     * * $idPeriodo
     * * $idCurso
     * * $idAsignatura
     * * $idObjetivo
     * * $tipo
     * @return \Illuminate\Http\Response
     */
    // public function getPromedioIndicadoresAlumno($idPeriodo, $idCurso, $idAsignatura, $idObjetivo, $idAlumno, $idEstablecimiento, $tipoObjetivo)
    // {
    //     $promedio = null;
    //     $puntajes = array();

    //     $tablaIndicador = $tipoObjetivo === 'Ministerio' ? 'indicadores' : 'indicadores_personalizados';
    //     $tipoIndicador = $tipoObjetivo === 'Ministerio' ? 'Normal' : 'Interno';
    //     // * $tipoPuntaje - Normal - Interno - Personalizado
    //     $puntajesIndicador =  DB::select(
    //         'SELECT
    //             pi.id
    //             , pi.idAlumno
    //             , pi.idIndicador
    //             , pi.puntaje
    //             , pi.tipoIndicador
    //             , pi.estado
    //             , pi.idUsuario_created
    //             , pi.idUsuario_updated
    //             , pi.created_at
    //             , pi.updated_at
    //             , i.idObjetivo as idObjetivoIndicador
    //         FROM puntajes_indicadores as pi
    //         LEFT JOIN ' . $tablaIndicador . ' as i
    //             ON pi.idIndicador = i.id
    //         WHERE
    //             pi.idAlumno = ' . $idAlumno . ' AND
    //             i.idObjetivo = ' . $idObjetivo . ' AND
    //             pi.idPeriodo = ' . $idPeriodo . ' AND
    //             pi.idCurso = ' . $idCurso . ' AND
    //             pi.idAsignatura = ' . $idAsignatura . ' AND
    //             pi.tipoIndicador = "' . $tipoIndicador . '" AND
    //             pi.estado = "Activo"
    //         '
    //     );

    //     $puntajesIndicadorPersonalizado =  DB::select(
    //         'SELECT
    //             pi.id
    //             , pi.idAlumno
    //             , pi.idIndicador
    //             , pi.puntaje
    //             , pi.tipoIndicador
    //             , pi.estado
    //             , pi.idUsuario_created
    //             , pi.idUsuario_updated
    //             , pi.created_at
    //             , pi.updated_at
    //             , i.tipo_objetivo
    //             , i.idObjetivo as idObjetivoIndicadorPersonalizado
    //         FROM puntajes_indicadores as pi
    //         LEFT JOIN indicador_personalizados as i
    //             ON pi.idIndicador = i.id
    //         WHERE
    //             pi.idAlumno = ' . $idAlumno . ' AND
    //             pi.idPeriodo = ' . $idPeriodo . ' AND
    //             pi.idCurso = ' . $idCurso . ' AND
    //             pi.idAsignatura = ' . $idAsignatura . ' AND
    //             pi.tipoIndicador = "Personalizado" AND
    //             pi.estado = "Activo" AND
    //             i.tipo_objetivo = "' . $tipoObjetivo . '" AND
    //             i.idObjetivo = ' . $idObjetivo . ' AND
    //             i.estado = "Aprobado"
    //         '
    //     );
    //     foreach ($puntajesIndicador as $key => $puntajeIndicador) {
    //         array_push($puntajes, $puntajeIndicador);
    //     }

    //     foreach ($puntajesIndicadorPersonalizado as $key => $puntajePersonalizado) {
    //         array_push($puntajes, $puntajePersonalizado);
    //     }

    //     // SETEA PUNTAJES ALUMNOS
    //     if ($puntajes) {
    //         $promedio = $this->getPromedioConversion($puntajes, $idEstablecimiento, $idPeriodo);
    //     } else {
    //         $promedio = 'undefined';
    //     }

    //     return $promedio;
    // }

    public function getPromedioIndicadoresAlumno($idPeriodo, $idCurso, $idAsignatura, $idObjetivo, $idAlumno, $idEstablecimiento, $tipoObjetivo)
    {
        // Definir la relación correcta según el tipo de objetivo
        $relacionIndicador = $tipoObjetivo === 'Ministerio' ? 'indicador' : 'indicadorPersonalizado';
        $tipoIndicador = $tipoObjetivo === 'Ministerio' ? 'Normal' : 'Interno';

        // Obtener los puntajes de indicadores asociados al objetivo
        $puntajes = PuntajeIndicador::with([$relacionIndicador => function ($query) use ($idObjetivo) {
            $query->where('idObjetivo', $idObjetivo);
        }])
            ->whereHas($relacionIndicador, function ($query) use ($idObjetivo) {
                $query->where('idObjetivo', $idObjetivo);
            }) // Solo traer registros si tienen indicador relacionado
        ->where('idPeriodo', $idPeriodo)
        ->where('idCurso', $idCurso)
        ->where('idAsignatura', $idAsignatura)
            ->where('idAlumno', $idAlumno)
        ->where('tipoIndicador', $tipoIndicador)
        ->where('estado', 'Activo')
        ->where('puntaje', '!=', 0) // Asegurar que solo se traigan puntajes válidos
            ->get();

        // Si no hay puntajes, retornar 'undefined'
        if ($puntajes->isEmpty()) {
            return 'undefined';
        }

        // Calcular el promedio de conversión
        return $this->getPromedioConversion($puntajes, $idEstablecimiento, $idPeriodo);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $puntaje)
    {
        $idPeriodo = $request->input('idPeriodo');
        $idCurso = $request->input('idCurso');
        $idAsignatura = $request->input('idAsignatura');
        $idObjetivo = $request->input('idObjetivo');
        $idIndicador = $request->input('idIndicador');
        $tipoObjetivo = $request->input('tipoObjetivo');
        $idAlumno = $request->input('idAlumno');
        $tipoIndicador = $request->input('tipoIndicador');
        $puntajeIndicador = PuntajeIndicador::findPuntajeIndicador(
            $idPeriodo,
            $idCurso,
            $idAsignatura,
            $idIndicador,
            $idAlumno,
            $tipoIndicador,
        );
        // return response()->json(['idPeriodo' => $idPeriodo, 'idCurso' => $idCurso, 'idAsignatura' => $idAsignatura, 'idIndicador' => $idIndicador, 'idAlumno' => $idAlumno, 'tipoIndicador' => $tipoIndicador]);
        $user = $request->user()->getUserData();
        logger()->info($request->input('puntaje'));
        if ($request->input('puntaje') === 0) {
            // ELIMINA
            try {
                $id = $puntajeIndicador[0]['id'];
                $PuntajeIndicador = PuntajeIndicador::findOrFail($id);
                $PuntajeIndicador->delete();
                $promedio = $this->getPromedioIndicadoresAlumno($idPeriodo, $idCurso, $idAsignatura, $idObjetivo, $idAlumno, $user['establecimiento']['id'], $tipoObjetivo);
                return response()->json(['status' => 'success', 'code' => 200, 'promedio' => $promedio]);
            } catch (\Throwable $th) {
                return response($th, 500);
            }
        }
        if (count($puntajeIndicador)) {
            // EDITA
            try {
                $id = $puntajeIndicador[0]['id'];
                $puntajeIndicador = PuntajeIndicador::findOrFail($id);

                $puntaje     = $request->input('puntaje');

                $usuarioUpdate = $user['id'];

                $puntajeIndicador->puntaje           = $puntaje;
                $puntajeIndicador->idUsuario_updated = $usuarioUpdate;

                $puntajeIndicador->save();
                $promedio = $this->getPromedioIndicadoresAlumno($idPeriodo, $idCurso, $idAsignatura, $idObjetivo, $idAlumno, $user['establecimiento']['id'], $tipoObjetivo);
                return response()->json(['status' => 'success', 'code' => 200, 'promedio' => $promedio]);
            } catch (\Throwable $th) {
                return response($th, 500);
            }
        } else {
            // CREA
            $usuarioCreate = $user['id'];
            try {
                $PuntajeIndicador = PuntajeIndicador::Create([
                    'idPeriodo'         => $request->input('idPeriodo'),
                    'idCurso'           => $request->input('idCurso'),
                    'idAsignatura'      => $request->input('idAsignatura'),
                    'idIndicador'       => $request->input('idIndicador'),
                    'idAlumno'          => $request->input('idAlumno'),
                    'puntaje'           => $request->input('puntaje'),
                    'tipoIndicador'     => $request->input('tipoIndicador'),
                    'estado'            => $request->input('estado'),
                    'idUsuario_created' => $usuarioCreate,
                ]);
                $promedio = $this->getPromedioIndicadoresAlumno($idPeriodo, $idCurso, $idAsignatura, $idObjetivo, $idAlumno, $user['establecimiento']['id'], $tipoObjetivo);
                return response()->json(['PuntajeIndicador' => $PuntajeIndicador, 'code' => 200, 'promedio' => $promedio]);
            } catch (\Throwable $th) {
                return response($th, 500);
            }
        }
    }

    /**
     * Obtiene el promedio del alumno
     * * $puntajes
     * * $idEstablecimiento
     * @return \Illuminate\Http\Response
     */
    public function getPromedioConversion($puntajes, $idEstablecimiento, $idPeriodo)
    {
        $puntajeObtenido = 0;
        foreach ($puntajes as $key => $puntaje) {
            $puntajeObtenido = $puntajeObtenido + $puntaje->puntaje;
        }
        $cantidadIndicadores = count($puntajes);
        $ajustes = Ajuste::getAjustes($idEstablecimiento, $idPeriodo);
        
        if ($ajustes->tipo_nota === 'concepto') {
            $promedio = $this->notasConversionController->getPromedio($cantidadIndicadores, $puntajeObtenido, $idPeriodo);
        } else if ($ajustes->tipo_nota === 'numero') {
            if (is_object($puntajes)) {
                $promedio['nota'] = $this->notasConversionController->getPromedioNota(json_decode(json_encode($puntajes)));
            } else {
                $promedio['nota'] = $this->notasConversionController->getPromedioNota($puntajes);
            }
        }
        return $promedio;
    }


    /**
     * Obtiene la transformación de puntajes a nota
     *
     * @return \Illuminate\Http\Response
     */
    public function getPuntajesIndicadoresTransformacion(Request $request)
    {
        $user = $request->user()->getUserData();
        return PuntajeIndicadorTransformacion::getPuntajes($user['periodo']['id']);
    }
}
