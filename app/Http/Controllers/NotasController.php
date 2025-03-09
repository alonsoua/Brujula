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
        $notasObjetivos = Notas::select('notas.*')
            ->leftJoin("objetivos", "objetivos.id", "=", "notas.idObjetivo")
            ->where('notas.idPeriodo', $idPeriodo)
            ->where('notas.idCurso', $idCurso)
            ->where('notas.idAsignatura', $idAsignatura)
            ->where('notas.tipoObjetivo', 'Ministerio')
            ->orderBy('objetivos.abreviatura')
            ->get();

        $notasObjetivosPersonalizados = Notas::select('notas.*')
            ->leftJoin("objetivos_personalizados", "objetivos_personalizados.id", "=", "notas.idObjetivo")
            ->where('notas.idPeriodo', $idPeriodo)
            ->where('notas.idCurso', $idCurso)
            ->where('notas.idAsignatura', $idAsignatura)
            ->where('notas.tipoObjetivo', 'Interno')
            ->orderBy('objetivos_personalizados.abreviatura')
            ->get();

        $notas = array();
        foreach ($notasObjetivos as $key => $nota) {
            array_push($notas, $nota);
        }

        foreach ($notasObjetivosPersonalizados as $key => $nota) {
            array_push($notas, $nota);
        }
        return $notas;
    }

    public function getAllNotasCurso($idPeriodo, $idCurso)
    {
        $notasMinisterio = Notas::select('notas.*', 'objetivos.abreviatura')
        ->leftJoin("objetivos", "objetivos.id", "=", "notas.idObjetivo")
        ->where('notas.idPeriodo', $idPeriodo)
            ->where('notas.idCurso', $idCurso)
        ->where('notas.tipoObjetivo', 'Ministerio');

        $notasInternas = Notas::select('notas.*', 'objetivos_personalizados.abreviatura')
        ->leftJoin("objetivos_personalizados", "objetivos_personalizados.id", "=", "notas.idObjetivo")
        ->where('notas.idPeriodo', $idPeriodo)
        ->where('notas.idCurso', $idCurso)
        ->where('notas.tipoObjetivo', 'Interno')
        ->unionAll($notasMinisterio) // 🔹 Une ambas consultas en una sola llamada
            ->orderBy('abreviatura')
            ->get();

        return $notasInternas;
    }

    public function getAll($idPeriodo, $idCurso)
    {
        // * Conexión Brújula > Libro digital
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
                // $store = $this->store($data);
                return 'store agregar nota';
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

    public function calcularNotaCurso(Request $request, $idCurso, $idAsignatura, $idPeriodo, $idObjetivo)
    {
        // consultar alumnos Activos del curso
        $alumnos = Alumno::getAlumnosCurso($idCurso);
        foreach ($alumnos as $key => $alumno) {
            $this->calcularNota($request, $alumno->id, $idCurso, $idAsignatura, $idPeriodo, $idObjetivo);
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
                    'nota'         => floatval($data['nota']),
                    'idAlumno'     => $data['idAlumno'],
                    'idCurso'      => $data['idCurso'],
                    'idAsignatura' => $data['idAsignatura'],
                    'idPeriodo'    => $data['idPeriodo'],
                    'idObjetivo'   => $data['idObjetivo'],
                    'tipoObjetivo' => $data['tipoObjetivo'],
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
    public function update($data)
    {
        try {
            $nota = Notas::findOrFail($data['idNota']);
            $nota->nota = $data['nota'];
            $nota->save();

            return response()->json(['status' => 'success', 'message' => 'Nota actualizada', 'data' => $data]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message update' => $th]);
            // return response($th, 500);
        }
    }

    public function updateNota(Request $request)
    {
        $nota = Notas::where([
            'idAlumno' => $request->input('idAlumno'),
            'idAsignatura' => $request->input('idAsignatura'),
            'idCurso' => $request->input('idCurso'),
            'idPeriodo' => $request->input('idPeriodo'),
            'idObjetivo' => $request->input('idObjetivo'),
            'tipoObjetivo' => $request->input('tipoObjetivo')
        ])->first();

        if (!$nota && $request->input('nota') !== 0) {
            // * Crear nueva nota
            return $this->store($request);
        } else if ($nota && $request->input('nota') !== 0) {
            // * Si la nota existe y la nueva NOTA NO ES "0, actualiza
            return $this->update([
                'idNota' => $nota->id,
                'nota' => $request->input('nota'),
            ]);
        } elseif ($nota && $request->input('nota') === 0) {
            // * Si la nota existe y la nueva NOTA ES "0", ELIMINA
            return $this->destroy($nota->id);
        }

        return response()->json(['status' => 'error', 'message' => 'Operación no válida']);
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
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function updateNotasScript(Request $request, $idCurso, $idGrado)
    // {
    //     try {
    //         $user = $request->user();
    //         $idEstablecimientoActivo = $user->idEstablecimientoActivo;
    //         $idPeriodo = null;
    //         $response = array();
    //         // foreach ($cursos as $key => $curso) {
    //         $asignaturas = $this->asignaturaController->getActivosGrado($idGrado);
    //         foreach ($asignaturas as $key => $asignatura) {
    //             $idAsignatura = $asignatura->id;
    //             $objetivos = $this->objetivoController->getObjetivosActivosAsignaturaEstablecimiento(
    //                 $idEstablecimientoActivo,
    //                 $idAsignatura
    //             );

    //             foreach ($objetivos as $key => $objetivo) {
    //                 $idObjetivo = $objetivo->id;
    //                 $tipoObjetivo = $objetivo->tipo;
    //                 $alumnosNotas = $this->puntajeIndicadorController->getPuntajesIndicadores(
    //                     $idPeriodo,
    //                     $idCurso,
    //                     $idAsignatura,
    //                     $idObjetivo,
    //                     $tipoObjetivo
    //                 );
    //                 // return response()->json(['asignatura' => $asignatura, 'alumnosNotas' => $alumnosNotas]);
    //                 foreach ($alumnosNotas as $key => $alumno) {
    //                     if ($alumno['promedio'] !== 'undefined') {

    //                         $idAlumno = $alumno['idAlumno'];
    //                         $promedio = $alumno['promedio'];
    //                         if (is_object($promedio)) {
    //                             $nota = $promedio->nota;
    //                             $data = array(
    //                                 'idCurso' => $idCurso,
    //                                 'idAlumno' => $idAlumno,
    //                                 'idAsignatura' => $idAsignatura,
    //                                 'idPeriodo' => $idPeriodo,
    //                                 'idObjetivo' => $idObjetivo,
    //                                 'tipoObjetivo' => $tipoObjetivo,
    //                                 'nota' => floatval($nota),
    //                             );

    //                             $res = $this->updateNotaNew($data);
    //                             array_push($response, array(
    //                                 'idCurso' => $data['idCurso'],
    //                                 'idAlumno' => $data['idAlumno'],
    //                                 'idAsignatura' => $data['idAsignatura'],
    //                                 'tipoObjetivo' => $data['tipoObjetivo'],
    //                                 'nota' => $data['nota'],
    //                                 'response' => $res,
    //                             ));
    //                             // return $response;
    //                         }
    //                     }
    //                 }
    //             }
    //         }
    //         // }
    //         return $response;
    //         // return response()->json(['status' => 'success', 'message' => 'Notas Actualizadas']);
    //     } catch (\Exception $th) {
    //         return response()->json(['status' => 'error', 'message' => $th]);
    //     }
    // }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // public function updateNotaNew($request)
    // {
    //     $nota = DB::select(
    //         'SELECT
    //             n.id,
    //             n.nota,
    //             n.tipoObjetivo
    //         FROM notas as n
    //         WHERE
    //             n.idAlumno = ' . $request['idAlumno'] . ' AND
    //             n.idAsignatura = ' . $request['idAsignatura'] . ' AND
    //             n.idCurso = ' . $request['idCurso'] . ' AND
    //             n.idPeriodo = ' . $request['idPeriodo'] . ' AND
    //             n.idObjetivo = ' . $request['idObjetivo'] . ' AND
    //             n.tipoObjetivo IS NULL
    //         '
    //     );
    //     // n.tipoObjetivo = "' . $request['tipoObjetivo'] . '"
    //     $response = null;

    //     if (count($nota) === 0 && $request['nota'] !== 0) { // CREATE
    //         $response = $this->store($request);
    //     } else if (count($nota) === 1) {
    //         if (is_null($nota[0]->tipoObjetivo)) {
    //             $data = array(
    //                 'idNota' => $nota[0]->id,
    //                 'nota' => $request['nota'],
    //                 'tipoObjetivo' => $request['tipoObjetivo'],
    //             );
    //             $response = $this->update($data);
    //         } else {
    //             $response = 'Nota ya formateada.';
    //         }
    //     } else if (count($nota) === 2) {
    //         // si ambas son null
    //         // $this->destroy($nota[1]->id);
    //         if ($nota[0]->tipoObjetivo === null && $nota[1]->tipoObjetivo === null) {
    //             $data = array(
    //                 'idNota' => $nota[0]->id,
    //                 'nota' => $request['nota'],
    //                 'tipoObjetivo' => $request['tipoObjetivo'],
    //             );
    //             $response = $this->update($data);
    //         }
    //         // else {
    //         //     return $nota;
    //         // }
    //     }
    //     return $response;
    // }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // public function updateNota(Request $request)
    // {
    //     $tipoObjetivo = $request['tipoObjetivo'];
    //     $nota = DB::select(
    //         'SELECT
    //             n.id,
    //             n.nota,
    //             n.tipoObjetivo
    //         FROM notas as n
    //         WHERE
    //             n.idAlumno = ' . $request['idAlumno'] . ' AND
    //             n.idAsignatura = ' . $request['idAsignatura'] . ' AND
    //             n.idCurso = ' . $request['idCurso'] . ' AND
    //             n.idPeriodo = ' . $request['idPeriodo'] . ' AND
    //             n.idObjetivo = ' . $request['idObjetivo'] . ' AND
    //             n.tipoObjetivo = "' . $tipoObjetivo . '"
    //         '
    //     );

    //     if (count($nota) === 0 && $request['nota'] !== 0) { // CREATE
    //         $nota2 = DB::select(
    //             'SELECT
    //                 n.id,
    //                 n.nota,
    //                 n.tipoObjetivo
    //             FROM notas as n
    //             WHERE
    //                 n.idAlumno = ' . $request['idAlumno'] . ' AND
    //                 n.idAsignatura = ' . $request['idAsignatura'] . ' AND
    //                 n.idCurso = ' . $request['idCurso'] . ' AND
    //                 n.idPeriodo = ' . $request['idPeriodo'] . ' AND
    //                 n.idObjetivo = ' . $request['idObjetivo'] . ' AND
    //                 n.tipoObjetivo IS NULL
    //             '
    //         );
    //         if (count($nota2) === 0 && $request['nota'] !== 0) {
    //             $response = $this->store($request);
    //         } else if (count($nota2) === 1) {
    //             $data = array(
    //                 'idNota' => $nota[0]->id,
    //                 'nota' => $request['nota'],
    //                 'tipoObjetivo' => $tipoObjetivo,
    //             );
    //             $response = $this->update($data);
    //         }
    //     } else if (count($nota) === 1 && $request['nota'] !== 0) { // UPDATE
    //         $data = array(
    //             'idNota' => $nota[0]->id,
    //             'nota' => $request['nota'],
    //             'tipoObjetivo' => $tipoObjetivo,
    //         );
    //         $response = $this->update($data);
    //     } else if ($request['nota'] === 0) { // Eliminar
    //         $response = $this->destroy($nota[0]->id);
    //     } else if (count($nota) === 2) {
    //         $response = $this->destroy($nota[0]->id);
    //         return response()->json(['status' => 'error', 'message' => 'Notas más de 2', 'notas' => $nota]);
    //     }

    //     return $response;
    // }
}
