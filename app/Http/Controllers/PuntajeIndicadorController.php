<?php

namespace App\Http\Controllers;

use App\Models\Master\Ajuste;
use App\Models\Notas;
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
        $relacionIndicador = $tipoObjetivo === 'Ministerio' ? 'indicador' : 'indicadoresPersonalizados';
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

    public function getPromedioIndicadoresAlumno($idPeriodo, $idCurso, $idAsignatura, $idObjetivo, $idAlumno, $user, $tipoObjetivo)
    {
        $evaluaciones_activo = $user['ajustes']['evaluaciones_activo'];
        $idEstablecimiento = $user['establecimiento']['id'];
        // Definir la relación correcta según el tipo de objetivo
        $relacionIndicador = $tipoObjetivo === 'Ministerio' ? 'indicador' : 'indicadoresPersonalizados';
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

        logger()->info($puntajes);
        // Si no hay puntajes, retornar 'undefined'
        if ($puntajes->isEmpty()) {
            return 'undefined';
        }

        $result = $this->getPromedioConversion($puntajes, $idEstablecimiento, $idPeriodo);
        if ($evaluaciones_activo === 1) {
            // Llamar a storeOrUpdate
            $data = [
                'idAlumno' => $idAlumno,
                'idCurso' => $idCurso,
                'idAsignatura' => $idAsignatura,
                'idPeriodo' => $idPeriodo,
                'idObjetivo' => $idObjetivo,
                'tipoObjetivo' => $tipoObjetivo,
                'nota' => $result['nota']
            ];
            $this->storeOrUpdateNota($data);
        }

        // Calcular el promedio de conversión
        return $result;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeOrUpdateNota($data)
    {
        try {
            DB::transaction(function () use ($data) {
                $nota = Notas::updateOrCreate(
                    [
                        'idAlumno'     => $data['idAlumno'],
                        'idCurso'      => $data['idCurso'],
                        'idAsignatura' => $data['idAsignatura'],
                        'idPeriodo'    => $data['idPeriodo'],
                        'idObjetivo'   => $data['idObjetivo'],
                        'tipoObjetivo' => $data['tipoObjetivo'],
                    ],
                    ['nota' => floatval($data['nota'])]
                );
                $nota->save();
                if ($nota->wasRecentlyCreated) {
                    return response()->json(['status' => 'success', 'message' => 'Nota creada']);
                } else {
                    return response()->json(['status' => 'success', 'message' => 'Nota actualizada']);
                }
            });
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
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

        $user = $request->user()->getUserData();
        if ($request->input('puntaje') === 0) {
            // ELIMINA
            try {
                if (count($puntajeIndicador) > 0) {
                    $id = $puntajeIndicador[0]['id'];
                    $PuntajeIndicador = PuntajeIndicador::findOrFail($id);
                    $PuntajeIndicador->delete();
                }
                $promedio = $this->getPromedioIndicadoresAlumno($idPeriodo, $idCurso, $idAsignatura, $idObjetivo, $idAlumno, $user, $tipoObjetivo);
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
                $promedio = $this->getPromedioIndicadoresAlumno($idPeriodo, $idCurso, $idAsignatura, $idObjetivo, $idAlumno, $user, $tipoObjetivo);
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
                    'estado'            => 'Activo',
                    'idUsuario_created' => $usuarioCreate,
                ]);
                $promedio = $this->getPromedioIndicadoresAlumno($idPeriodo, $idCurso, $idAsignatura, $idObjetivo, $idAlumno, $user, $tipoObjetivo);
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
