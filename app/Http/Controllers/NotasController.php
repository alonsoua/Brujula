<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PuntajeIndicador;
use App\Models\Establecimiento;
use App\Models\NotasConversion;
use App\Models\Alumno;
use App\Models\Notas;
use Illuminate\Support\Facades\DB;

class NotasController extends Controller
{

    /**
     * @var puntajeIndicadorController
     */
    protected $puntajeIndicadorController;

    /**
     * @var cursoController
     */
    protected $cursoController;

    /**
     * @var asignaturaController
     */
    protected $asignaturaController;

    /**
     * @var objetivoController
     */
    protected $objetivoController;

    public function __construct()
    {
        $this->cursoController = app('App\Http\Controllers\CursoController');
        $this->asignaturaController = app('App\Http\Controllers\AsignaturaController');
        $this->objetivoController = app('App\Http\Controllers\ObjetivoController');

        $this->puntajeIndicadorController = app('App\Http\Controllers\PuntajeIndicadorController');
    }

    public function getNotasAsignatura($idPeriodo, $idCurso, $idAsignatura)
    {
        return Notas::select('*')
            ->where('idPeriodo', $idPeriodo)
            ->where('idCurso', $idCurso)
            ->where('idAsignatura', $idAsignatura)
            ->get();
    }

    public function getAllNotasCurso($idPeriodo, $idCurso)
    {
        return Notas::getAllNotasCurso(
            $idPeriodo,
            $idCurso,
        );
    }

    public function getAll($idPeriodo, $idCurso)
    {
        // Conexión Brújula > Libro digital
        // join rutAlumno
        // join nombrePeriodo
        // join id_grado del curso, nombre curso y letra
        // join nombreAsignatura
        // join nombreObjetivo o abreviatura.
        // obtener rutDocente a cargo de esa asignatura.
        // return response()->json(['status' => 'ACA']);
        try {
            $response = Notas::select(
                'notas.*',
                'alumnos.tipoDocumento',
                'alumnos.rut as rutAlumno',
                'alumnos.nombres as nombreAlumno',
                'alumnos.primerApellido',
                'alumnos.segundoApellido',
                'periodos.nombre as nombrePeriodo',
                'grados.idGrado as idGrado',
                'grados.idNivel as nivelGrado',
                'grados.nombre as nombreGrado',
                'cursos.letra',
                'asignaturas.nombre as nombreAsignatura',
                // 'users.rut as rutDocente'
            )
                ->leftJoin("alumnos", "alumnos.id", "=", "notas.idAlumno")
                ->leftJoin("periodos", "periodos.id", "=", "notas.idPeriodo")
                ->leftJoin("cursos", "cursos.id", "=", "notas.idCurso")
                ->leftJoin("grados", "grados.id", "=", "cursos.idGrado")
                ->leftJoin("asignaturas", "asignaturas.id", "=", "notas.idAsignatura")
                // ->leftJoin("usuario_asignaturas", "usuario_asignaturas.idAsignatura", "=", "notas.idAsignatura")
                // ->leftJoin("usuario_establecimientos", "usuario_establecimientos.id", "=", "usuario_asignaturas.idUsuarioEstablecimiento")
                // ->leftJoin("users", "users.id", "=", "usuario_establecimientos.idUsuario")
                ->where('notas.idPeriodo', $idPeriodo)
                ->where('notas.idCurso', $idCurso)
                ->where('asignaturas.estado', 'Activo')
                ->where('cursos.estado', 'Activo')
                ->get();

            return $response;
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'response' => $th]);
        }
        // return $response;
    }


    public function calcularNota(Request $request, $idAlumno, $idCurso, $idAsignatura, $idPeriodo, $idObjetivo)
    {
        $user = $request->user();
        $establecimiento = Establecimiento::getAll($user->idEstablecimientoActivo);
        $puntajesNormal = PuntajeIndicador::getPuntajesAlumno(
            $idPeriodo,
            $idAlumno,
            $idAsignatura,
            $idObjetivo
        );
        $puntajesPersonalizados = PuntajeIndicador::getPuntajesAlumnoPersonalizado(
            $idPeriodo,
            $idAlumno,
            $idAsignatura,
            $idObjetivo
        );

        $puntajes = array();
        foreach ($puntajesNormal as $pn => $puntaje) {
            array_push($puntajes, $puntaje);
        }

        foreach ($puntajesPersonalizados as $pp => $puntajePersonalizado) {
            array_push($puntajes, $puntajePersonalizado);
        }

        $notas = Notas::getNotaObjetivo($idAlumno, $idCurso, $idPeriodo, $idAsignatura, $idObjetivo);
        $cantidadPuntajes = count($puntajes);
        if ($cantidadPuntajes > 0) {
            $puntajeObtenido = 0;
            foreach ($puntajes as $key => $puntaje) {
                $puntajeObtenido += $puntaje->puntaje;
            }

            $cantidadIndicadores = count($puntajes);
            if ($puntajeObtenido > 0) {
                $notaConversion = NotasConversion::getNotasConversion($cantidadIndicadores, $puntajeObtenido, $establecimiento[0]->idPeriodoActivo, $user->idEstablecimientoActivo);
                $notaConvertida = $notaConversion[0]->nota;
            } else {
                $notaConvertida = '2.0';
            }

            if (count($notas) > 0) {
                $data = array(
                    'nota' => $notaConvertida,
                    'idNota' => $notas[0]->id,
                );
                $update = $this->update($data);
                return $update;
            } else {
                $data = array(
                    'nota' => $notaConvertida,
                    'idAlumno' => $idAlumno,
                    'idCurso' => $idCurso,
                    'idAsignatura' => $idAsignatura,
                    'idPeriodo' => $idPeriodo,
                    'idObjetivo' => $idObjetivo,
                );
                $store = $this->store($data);
                return $store;
            }
        } else {
            // si existe nota, la elimina
            // si no existe nota no hace nada
            if (count($notas) > 0) {
                $destroy = $this->destroy($notas[0]->id);
                return $destroy;
            }
        }
    }

    public function calcularNotaCurso($idCurso, $idAsignatura, $idPeriodo, $idObjetivo)
    {
        // consultar alumnos Activos del curso
        $alumnos = Alumno::getAlumnosCurso($idCurso);
        foreach ($alumnos as $key => $alumno) {
            $this->calcularNota($alumno->id, $idCurso, $idAsignatura, $idPeriodo, $idObjetivo);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store($data)
    {
        try {
            DB::transaction(function () use ($data) {
                Notas::Create([
                    'nota'        => floatval($data['nota']),
                    'idAlumno'    => $data['idAlumno'],
                    'idCurso'     => $data['idCurso'],
                    'idAsignatura' => $data['idAsignatura'],
                    'idPeriodo'   => $data['idPeriodo'],
                    'idObjetivo'  => $data['idObjetivo'],
                ]);
            });
            return response()->json(['status' => 'success', 'message' => 'Nota Creada']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message create' => $th]);
            // return response($th, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateNotasScript(Request $request)
    {
        // try {
        $user = $request->user();
        $idEstablecimientoActivo = $user->idEstablecimientoActivo;
        $cursos = $this->cursoController->getActivos($request);
        $idPeriodo = 4;
        $response = array();
        foreach ($cursos as $key => $curso) {
            $idCurso = $curso->id;
            $asignaturas = $this->asignaturaController->getActivosGrado($curso->idGrado);

            foreach ($asignaturas as $key => $asignatura) {
                $idAsignatura = $asignatura->id;
                $objetivos = $this->objetivoController->getObjetivosActivosAsignaturaEstablecimiento($idEstablecimientoActivo, $idAsignatura);

                foreach ($objetivos as $key => $objetivo) {
                    $idObjetivo = $objetivo->id;
                    $tipoObjetivo = $objetivo->tipo;
                    $alumnosNotas = $this->puntajeIndicadorController->getPuntajesIndicadores($idPeriodo, $idCurso, $idAsignatura, $idObjetivo, $tipoObjetivo);
                    foreach ($alumnosNotas as $key => $alumno) {
                        if ($alumno['promedio'] !== 'undefined') {
                            $idAlumno = $alumno['idAlumno'];
                            $promedio = $alumno['promedio'];
                            if (is_object($promedio)) {
                                $nota = $promedio->nota;
                                $data = array(
                                    'idCurso' => $idCurso,
                                    'idAlumno' => $idAlumno,
                                    'idAsignatura' => $idAsignatura,
                                    'idPeriodo' => $idPeriodo,
                                    'idObjetivo' => $idObjetivo,
                                    'nota' => floatval($nota),
                                );
                                $res = $this->updateNotaNew($data);

                                array_push($response, array(
                                    'data' => $data,
                                    'response' => $res,
                                ));
                            }
                            // $idAsignatura
                            // $idCurso
                            // $idPeriodo == 4
                            // $idObjetivo

                            // $response = json_decode($res, false);
                            // // return $response->status;
                            // if ($response->status === 'error') {
                            //     return $response;
                            // }
                        }
                    }
                }
            }
        }
        return $response;
        return response()->json(['status' => 'success', 'message' => 'Notas Actualizadas']);
        // } catch (\Throwable $th) {
        //     return response()->json(['status' => 'error', 'message' => $th]);
        // }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateNotaNew($request)
    {
        $nota = DB::select(
            'SELECT
                n.id
            FROM notas as n
            WHERE
                n.idAlumno = ' . $request['idAlumno'] . ' AND
                n.idAsignatura = ' . $request['idAsignatura'] . ' AND
                n.idCurso = ' . $request['idCurso'] . ' AND
                n.idPeriodo = ' . $request['idPeriodo'] . ' AND
                n.idObjetivo = ' . $request['idObjetivo'] . '
            '
        );

        if (count($nota) === 0 && $request['nota'] !== 0) { // CREATE
            $response = $this->store($request);
        } else if (count($nota) === 1 && $request['nota'] !== 0) { // UPDATE
            $data = array(
                'idNota' => $nota[0]->id,
                'nota' => $request['nota'],
            );
            $response = $this->update($data);
        } else if ($request['nota'] === 0) { // Eliminar
            $response = $this->destroy($nota[0]->id);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Notas Duplicadas']);
        }

        return $response;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($data)
    {
        try {
            $nota = Notas::findOrFail($data['idNota']);

            $nota->nota = $data['nota'];
            $nota->save();

            return response()->json(['status' => 'success', 'message' => 'Nota Actualizada']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message update' => $th]);
            // return response($th, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateNota(Request $request)
    {
        $nota = DB::select(
            'SELECT
                n.id
            FROM notas as n
            WHERE
                n.idAlumno = ' . $request['idAlumno'] . ' AND
                n.idAsignatura = ' . $request['idAsignatura'] . ' AND
                n.idCurso = ' . $request['idCurso'] . ' AND
                n.idPeriodo = ' . $request['idPeriodo'] . ' AND
                n.idObjetivo = ' . $request['idObjetivo'] . '
            '
        );

        if (count($nota) === 0 && $request['nota'] !== 0) { // CREATE
            $response = $this->store($request);
        } else if (count($nota) === 1 && $request['nota'] !== 0) { // UPDATE
            $data = array(
                'idNota' => $nota[0]->id,
                'nota' => $request['nota'],
            );
            $response = $this->update($data);
        } else if ($request['nota'] === 0) { // Eliminar
            $response = $this->destroy($nota[0]->id);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Notas Duplicadas']);
        }

        return $response;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $nota = Notas::findOrFail($id);
            $nota->delete();
            return response()->json(['status' => 'success', 'message' => 'Nota Eliminada']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message delete' => $th]);
            // return response($th, 500);
        }
    }
}
