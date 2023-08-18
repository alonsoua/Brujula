<?php

namespace App\Http\Controllers;

use App\Models\PuntajeIndicador;
use App\Models\PuntajeIndicadorTransformacion;
use App\Models\Establecimiento;
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
    public function getPuntajesIndicadores($idPeriodo, $idCurso, $idAsignatura, $idObjetivo, $tipoIndicador)
    {
        $alumnos = $this->alumnoController->getAlumnosCurso($idCurso);
        $alumnosPuntajes = array();
        if ($tipoIndicador === 'Ministerio') {
            $puntajes = array();
            $puntajesIndicador =  DB::select(
                'SELECT
                    pi.id
                    , pi.idAlumno
                    , pi.idIndicador
                    , pi.puntaje
                    , pi.tipoIndicador
                    , pi.estado
                    , pi.idUsuario_created
                    , pi.idUsuario_updated
                    , pi.created_at
                    , pi.updated_at
                    , i.idObjetivo as idObjetivoIndicador
                FROM puntajes_indicadores as pi
                LEFT JOIN indicadores as i
                    ON pi.idIndicador = i.id
                WHERE
                    i.idObjetivo = ' . $idObjetivo . ' AND
                    pi.idPeriodo = ' . $idPeriodo . ' AND
                    pi.idCurso = ' . $idCurso . ' AND
                    pi.idAsignatura = ' . $idAsignatura . ' AND
                    pi.tipoIndicador = "Normal" AND
                    pi.estado = "Activo"
                '
            );
            $puntajesIndicadorPersonalizado =  DB::select(
                'SELECT
                    pi.id
                    , pi.idAlumno
                    , pi.idIndicador
                    , pi.puntaje
                    , pi.tipoIndicador
                    , pi.estado
                    , pi.idUsuario_created
                    , pi.idUsuario_updated
                    , pi.created_at
                    , pi.updated_at
                    , i.tipo_objetivo
                    , i.idObjetivo as idObjetivoIndicadorPersonalizado
                FROM puntajes_indicadores as pi
                LEFT JOIN indicador_personalizados as i
                    ON pi.idIndicador = i.id
                WHERE
                    i.idObjetivo = ' . $idObjetivo . ' AND
                    pi.idPeriodo = ' . $idPeriodo . ' AND
                    pi.idCurso = ' . $idCurso . ' AND
                    pi.idAsignatura = ' . $idAsignatura . ' AND
                    pi.tipoIndicador = "Personalizado" AND
                    pi.estado = "Activo" AND
                    i.tipo_objetivo = "Ministerio" AND
                    i.estado = "Aprobado"
                '
            );
            foreach ($puntajesIndicador as $key => $puntajeIndicador) {
                array_push($puntajes, $puntajeIndicador);
            }
            foreach ($puntajesIndicadorPersonalizado as $key => $puntajePersonalizado) {
                array_push($puntajes, $puntajePersonalizado);
            }

            // SETEA PUNTAJES ALUMNOS
            foreach ($alumnos as $key => $alumno) {
                $puntajes_alumno = array();
                foreach ($puntajes as $key => $puntaje) {
                    if ($puntaje->idAlumno === intval($alumno['id'])) {
                        array_push($puntajes_alumno, $puntaje);
                    }
                }

                if ($puntajes) {
                    $promedio = $this->getPromedioConversion($puntajes_alumno, $alumno['idEstablecimiento']);
                } else {
                    $promedio = 'undefined';
                }

                array_push($alumnosPuntajes, array(
                    'idAlumno' => $alumno['id'],
                    'puntajes' => $puntajes_alumno,
                    'promedio' => $promedio,
                ));
            }
        } else if ($tipoIndicador === 'Interno') {
            $puntajes = array();
            $puntajesIndicador =  DB::select(
                'SELECT
                    pi.id
                    , pi.idAlumno
                    , pi.idIndicador
                    , pi.puntaje
                    , pi.tipoIndicador
                    , pi.estado
                    , pi.idUsuario_created
                    , pi.idUsuario_updated
                    , pi.created_at
                    , pi.updated_at
                    , i.idObjetivo as idObjetivoIndicador
                FROM puntajes_indicadores as pi
                LEFT JOIN indicadores_personalizados as i
                    ON pi.idIndicador = i.id
                WHERE
                    i.idObjetivo = ' . $idObjetivo . ' AND
                    pi.idPeriodo = ' . $idPeriodo . ' AND
                    pi.idCurso = ' . $idCurso . ' AND
                    pi.idAsignatura = ' . $idAsignatura . ' AND
                    pi.tipoIndicador = "Interno" AND
                    pi.estado = "Activo"
                '
            );
            foreach ($puntajesIndicador as $key => $puntajeIndicador) {
                array_push($puntajes, $puntajeIndicador);
            }

            // SETEA PUNTAJES ALUMNOS
            foreach ($alumnos as $key => $alumno) {
                $puntajes_alumno = array();
                foreach ($puntajes as $key => $puntaje) {
                    if ($puntaje->idAlumno === intval($alumno['id'])) {
                        array_push($puntajes_alumno, $puntaje);
                    }
                }

                if ($puntajes) {
                    $promedio = $this->getPromedioConversion($puntajes_alumno, $alumno['idEstablecimiento']);
                } else {
                    $promedio = 'undefined';
                }

                array_push($alumnosPuntajes, array(
                    'idAlumno' => $alumno['id'],
                    'puntajes' => $puntajes_alumno,
                    'promedio' => $promedio,
                ));
            }
        }

        return $alumnosPuntajes;
    }



    /**
     * Obtiene el promedio del alumno
     * * $puntajes
     * * $idEstablecimiento
     * @return \Illuminate\Http\Response
     */
    public function getPromedioConversion($puntajes, $idEstablecimiento)
    {
        $puntajeObtenido = 0;
        foreach ($puntajes as $key => $puntaje) {
            $puntajeObtenido = $puntajeObtenido + $puntaje->puntaje;
        }
        $cantidadIndicadores = count($puntajes);
        $promedio = $this->notasConversionController->getPromedio($cantidadIndicadores, $puntajeObtenido, $idEstablecimiento);
        return $promedio;
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getPuntajesIndicadoresTransformacion(Request $request)
    {
        $user = $request->user();
        $establecimiento = Establecimiento::getAll($user->idEstablecimientoActivo);
        return PuntajeIndicadorTransformacion::getPuntajes($establecimiento[0]->idPeriodoActivo, $user->idEstablecimientoActivo);
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
    public function getPuntajesIndicadoresAlumno($idPeriodo, $idCurso, $idAsignatura, $idObjetivo, $tipoIndicador, $idAlumno, $idEstablecimiento)
    {
        $promedio = null;
        // if ($tipoIndicador === 'Personalizado') {
        // } else {
        // }
        $puntajes = array();
        $puntajesIndicador =  DB::select(
            'SELECT
                pi.id
                , pi.idAlumno
                , pi.idIndicador
                , pi.puntaje
                , pi.tipoIndicador
                , pi.estado
                , pi.idUsuario_created
                , pi.idUsuario_updated
                , pi.created_at
                , pi.updated_at
                , i.idObjetivo as idObjetivoIndicador
            FROM puntajes_indicadores as pi
            LEFT JOIN indicadores as i
                ON pi.idIndicador = i.id
            WHERE
                pi.idAlumno = ' . $idAlumno . ' AND
                i.idObjetivo = ' . $idObjetivo . ' AND
                pi.idPeriodo = ' . $idPeriodo . ' AND
                pi.idCurso = ' . $idCurso . ' AND
                pi.idAsignatura = ' . $idAsignatura . ' AND
                pi.tipoIndicador = "Normal" AND
                pi.estado = "Activo"
            '
        );
        $puntajesIndicadorPersonalizado =  DB::select(
            'SELECT
                pi.id
                , pi.idAlumno
                , pi.idIndicador
                , pi.puntaje
                , pi.tipoIndicador
                , pi.estado
                , pi.idUsuario_created
                , pi.idUsuario_updated
                , pi.created_at
                , pi.updated_at
                , i.tipo_objetivo
                , i.idObjetivo as idObjetivoIndicadorPersonalizado
            FROM puntajes_indicadores as pi
            LEFT JOIN indicador_personalizados as i
                ON pi.idIndicador = i.id
            WHERE
                pi.idAlumno = ' . $idAlumno . ' AND
                i.idObjetivo = ' . $idObjetivo . ' AND
                pi.idPeriodo = ' . $idPeriodo . ' AND
                pi.idCurso = ' . $idCurso . ' AND
                pi.idAsignatura = ' . $idAsignatura . ' AND
                pi.tipoIndicador = "Personalizado" AND
                pi.estado = "Activo" AND
                i.tipo_objetivo = "Ministerio" AND
                i.estado = "Aprobado"
            '
        );
        foreach ($puntajesIndicador as $key => $puntajeIndicador) {
            array_push($puntajes, $puntajeIndicador);
        }
        foreach ($puntajesIndicadorPersonalizado as $key => $puntajePersonalizado) {
            array_push($puntajes, $puntajePersonalizado);
        }

        // SETEA PUNTAJES ALUMNOS
        if ($puntajes) {
            $promedio = $this->getPromedioConversion($puntajes, $idEstablecimiento);
        } else {
            $promedio = 'undefined';
        }

        return $promedio;
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
        // buscar el puntaje
        $idPeriodo = $request->input('idPeriodo');
        $idCurso = $request->input('idCurso');
        $idAsignatura = $request->input('idAsignatura');
        $idObjetivo = $request->input('idObjetivo');
        $idIndicador = $request->input('idIndicador');
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

        $user = $request->user();
        if (is_null($request->input('puntaje'))) {
            try {
                $id = $puntajeIndicador[0]['id'];
                $PuntajeIndicador = PuntajeIndicador::findOrFail($id);
                $PuntajeIndicador->delete();
                $promedio = $this->getPuntajesIndicadoresAlumno($idPeriodo, $idCurso, $idAsignatura, $idObjetivo, $tipoIndicador, $idAlumno, $user->idEstablecimientoActivo);
                return response()->json(['status' => 'success', 'code' => 200, 'promedio' => $promedio]);
            } catch (\Throwable $th) {
                return response($th, 500);
            }
        }
        if (count($puntajeIndicador)) {
            try {

                $id = $puntajeIndicador[0]['id'];
                $puntajeIndicador = PuntajeIndicador::findOrFail($id);

                $puntaje     = $request->input('puntaje');

                $usuarioUpdate = $user->id;

                $puntajeIndicador->puntaje           = $puntaje;
                $puntajeIndicador->idUsuario_updated = $usuarioUpdate;

                $puntajeIndicador->save();
                $promedio = $this->getPuntajesIndicadoresAlumno($idPeriodo, $idCurso, $idAsignatura, $idObjetivo, $tipoIndicador, $idAlumno, $user->idEstablecimientoActivo);
                return response()->json(['status' => 'success', 'code' => 200, 'promedio' => $promedio]);
            } catch (\Throwable $th) {
                return response($th, 500);
            }
        } else {
            $usuarioCreate = $user->id;
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
                $promedio = $this->getPuntajesIndicadoresAlumno($idPeriodo, $idCurso, $idAsignatura, $idObjetivo, $tipoIndicador, $idAlumno, $user->idEstablecimientoActivo);
                return response()->json(['PuntajeIndicador' => $PuntajeIndicador, 'code' => 200, 'promedio' => $promedio]);
            } catch (\Throwable $th) {
                return response($th, 500);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
