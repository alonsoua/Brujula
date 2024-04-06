<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Alumnos_Cursos;
use App\Models\Establecimiento;
use App\Models\Curso;
use App\Models\Grado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class AlumnoController extends Controller
{
    protected $establecimientoController;
    protected $cursoController;

    public function __construct()
    {
        $this->establecimientoController = app('App\Http\Controllers\EstablecimientoController');
        $this->cursoController = app('App\Http\Controllers\CursoController');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $idPeriodo = $user->idPeriodoActivo;
        if ($idPeriodo === null) {
            $establecimiento = Establecimiento::getAll($user->idEstablecimientoActivo);
            $idPeriodo = $establecimiento[0]['idPeriodoActivo'];
        }
        $alumnos = Alumno::select(
            'alumnos.*',
            'alumnos_cursos.estado',
            'prioritarios.nombre as nombrePrioritario',
            'diagnosticos_pie.nombre as nombreDiagnostico',
            'diagnosticos_pie.tipoNee as tipoNee',
            'cursos.letra',
            'grados.nombre as nombreGrado',
            'establecimientos.nombre as nombreEstablecimiento'
        )
            ->leftJoin("prioritarios", "alumnos.idPrioritario", "=", "prioritarios.id")
            ->leftJoin("diagnosticos_pie", "alumnos.idDiagnostico", "=", "diagnosticos_pie.id")
            ->leftJoin("alumnos_cursos", "alumnos.id", "=", "alumnos_cursos.idAlumno")
            ->leftJoin("cursos", "alumnos_cursos.idCurso", "=", "cursos.id")
            ->leftJoin("grados", "cursos.idGrado", "=", "grados.id")
            ->leftJoin("establecimientos", "alumnos.idEstablecimiento", "=", "establecimientos.id");
        if (!is_null($user->idEstablecimientoActivo)) {
            $alumnos = $alumnos->where('cursos.idEstablecimiento', $user->idEstablecimientoActivo);
        }
        if (!is_null($idPeriodo)) {
            $alumnos = $alumnos->where('cursos.idPeriodo', $idPeriodo);
        }
        $alumnos = $alumnos->orderBy('establecimientos.id')
            ->orderBy('grados.id')
            ->orderBy('cursos.letra')
            ->orderBy('alumnos.numLista')
            ->get();
        return $alumnos;
    }

    public function getAlumnosPeriodo(Request $request)
    {
        $user = $request->user();
        $idPeriodo = $user->idPeriodoActivo;
        if ($idPeriodo === null) {
            $establecimiento = Establecimiento::getAll($user->idEstablecimientoActivo);
            $idPeriodo = $establecimiento[0]['idPeriodoActivo'];
        }
        return Alumno::getAll($user->idEstablecimientoActivo, $idPeriodo);
    }

    public function getAlumnosCurso($idCurso)
    {
        return Alumno::select(
            'alumnos.*',
            'alumnos_cursos.idCurso',
            'alumnos_cursos.estado',
            'prioritarios.nombre as nombrePrioritario',
            'diagnosticos_pie.nombre as nombreDiagnostico',
            'diagnosticos_pie.abreviatura as abreviaturaDiagnostico',
            'diagnosticos_pie.tipoNee as tipoNee'
        )
            ->leftJoin("prioritarios", "alumnos.idPrioritario", "=", "prioritarios.id")
            ->leftJoin("diagnosticos_pie", "alumnos.idDiagnostico", "=", "diagnosticos_pie.id")
            ->leftJoin("alumnos_cursos", "alumnos.id", "=", "alumnos_cursos.idAlumno")
            ->where('alumnos_cursos.idCurso', $idCurso)
            ->where('alumnos_cursos.estado', 'Activo')
            ->orderBy('alumnos.numLista')
            ->get();
        // return Alumno::getAlumnosCurso($idCurso);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        Request()->validate([
            'tipoDocumento' => 'required|max:4',
            'rut' => 'required|max:15|unique:alumnos',
            'nombres' => 'required|max:250',
            'primerApellido' => 'required|max:250',
            'segundoApellido' => 'required|max:250',
            'fechaNacimiento' => 'required|max:250',
            'genero' => 'required|max:10',
            'idEstablecimiento' => 'required',
            'idCurso' => 'required',
            'estado' => 'required',
        ]);
        try {
            DB::transaction(function () use ($request) {

                $fechaInscripcion = date('Y-m-d H:i:s');
                $idEstablecimiento = $request->input('idEstablecimiento');
                $idCurso           = $request->input('idCurso');

                $alumnos =  Alumno::getAlumnosCursoEstablecimiento(
                    $idCurso,
                    $idEstablecimiento
                );

                $numLista = count($alumnos) + 1;
                $pie = $request->input('pie');

                $diagnosticoPie = !is_null($pie)
                    ? $request->input('idDiagnostico')
                    : null;

                $alumno = Alumno::Create([
                    'fechaInscripcion'  => $fechaInscripcion,
                    'numMatricula'      => $request->input('numMatricula'),
                    'tipoDocumento'     => $request->input('tipoDocumento'),
                    'rut'               => $request->input('rut'),
                    'nombres'           => $request->input('nombres'),
                    'primerApellido'    => $request->input('primerApellido'),
                    'segundoApellido'   => $request->input('segundoApellido'),
                    'correo'            => $request->input('correo'),
                    'genero'            => $request->input('genero'),
                    'fechaNacimiento'   => $request->input('fechaNacimiento'),
                    'paci'              => $request->input('paci'),
                    'pie'               => $pie,
                    'numLista'          => $numLista,
                    'idDiagnostico'     => $diagnosticoPie,
                    'idPrioritario'     => $request->input('idPrioritario'),
                    'idEstablecimiento' => $idEstablecimiento,
                ]);


                Alumnos_Cursos::Create([
                    'idAlumno' => $alumno->id,
                    'idCurso'  => $idCurso,
                    'estado'   => $request->input('estado'),
                ]);
            });
            return response()->json([
                'status' => 'Success',
                'message' => 'Alumno creado con éxito.'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Error:' . $th
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // return response($request, 500);
        Request()->validate([
            'idEstablecimiento' => 'required',
            'idCurso' => 'required',
            'rut' => 'required|max:15|unique:alumnos,rut,' . $id . ',id',
            'tipoDocumento' => 'required|max:4',
            'nombres' => 'required|max:250',
            'primerApellido' => 'required|max:250',
            'segundoApellido' => 'required|max:250',
            'genero' => 'required|max:10',
            'fechaNacimiento' => 'required|max:250',
            'estado' => 'required',
        ]);

        try {
            $alumno = Alumno::findOrFail($id);

            $idEstablecimiento = $request->input('idEstablecimiento');
            $idCurso           = $request->input('idCurso');
            $numMatricula    = $request->input('numMatricula');
            $nombres         = $request->input('nombres');
            $primerApellido  = $request->input('primerApellido');
            $segundoApellido = $request->input('segundoApellido');
            $correo          = $request->input('correo');
            $tipoDocumento   = $request->input('tipoDocumento');
            $rut             = $request->input('rut');
            $genero          = $request->input('genero');
            $fechaNacimiento = $request->input('fechaNacimiento');
            $paci            = $request->input('paci');
            $pie             = $request->input('pie');
            $diagnosticoPie  = !is_null($pie)
                ? $request->input('idDiagnostico')
                : null;
            $idPrioritario   = $request->input('idPrioritario');
            $estado          = $request->input('estado');

            if ($paci == 0) {
                $paci = null;
            }
            if ($pie == 0) {
                $pie = null;
            }
            $alumno->idEstablecimiento = $idEstablecimiento;
            $alumno->numMatricula    = $numMatricula;
            $alumno->nombres         = $nombres;
            $alumno->primerApellido  = $primerApellido;
            $alumno->segundoApellido = $segundoApellido;
            $alumno->correo          = $correo;
            $alumno->tipoDocumento   = $tipoDocumento;
            $alumno->rut             = $rut;
            $alumno->genero          = $genero;
            $alumno->fechaNacimiento = $fechaNacimiento;
            $alumno->paci            = $paci;
            $alumno->pie             = $pie;
            $alumno->idDiagnostico   = $diagnosticoPie;
            $alumno->idPrioritario   = $idPrioritario;
            if ($alumno->save()) {
                $alumnos_cursos = Alumnos_Cursos::where('idAlumno', $id)->where('idCurso', $idCurso)->where('estado', 'Activo')->get();
                if (count($alumnos_cursos) !== 0) {
                    $alumno_c = Alumnos_Cursos::findOrFail($alumnos_cursos[0]['id']);
                    $alumno_c->estado = 'Inactivo';
                    $alumno_c->save();
                }
                Alumnos_Cursos::Create([
                    'idAlumno' => $id,
                    'idCurso'  => $idCurso,
                    'estado'   => $estado,
                ]);
            }

            return response()->json([
                'status' => 'Success',
                'message' => 'Alumno editado con éxito.'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'Update Error',
                'message' => 'Error:' . $th
            ]);
        }
    }


    public function importAlumnosCSV(Request $request)
    {
        $documento = $request->file('lista');
        $name = time() . '.' . $documento->getClientOriginalExtension();
        $destinationPath = storage_path('/app/Imports');
        $documento->move($destinationPath, $name);

        $FileName = storage_path('/app/Imports') . "/" . $name;
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();

        $spreadsheet = $reader->load($FileName);

        $sheet = $spreadsheet->getSheet(0);
        $rbd = $sheet->getCell("B2")->getValue();
        try {
            $establecimiento = $this->establecimientoController->getrbd($rbd);
            if ($establecimiento != null) {
                $i = 2;

                foreach ($sheet->getRowIterator() as $row) {

                    $fecha_nacimiento = $sheet->getCell('S' . $i)->getValue();
                    $datoscurso = $this->cursoController->getCursoMatricula(
                        $sheet->getCell('C' . $i)->getValue(),
                        $sheet->getCell('D' . $i)->getValue(),
                        $sheet->getCell('F' . $i)->getValue(),
                        $establecimiento->idPeriodoActivo,
                        $establecimiento->id
                    );

                    if ($datoscurso == null) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'sin curso' . $sheet->getCell('C' . $i)->getValue() . '' .
                                $sheet->getCell('D' . $i)->getValue() . '' .
                                $sheet->getCell('F' . $i)->getValue() . ' FIN ' . $i . ''
                        ]);
                        $grados = Grado::where('idGrado', $sheet->getCell('D' . $i)->getValue())
                            ->where('idNivel', $sheet->getCell('C' . $i)->getValue())
                            ->first();

                        $request_curso = new Request([
                            'letra'             => $sheet->getCell('F' . $i)->getValue(),
                            'idGrado'           => $grados->id,
                            'idEstablecimiento' => $establecimiento->id,
                            'idPeriodo'         => $establecimiento->idPeriodoActivo,
                        ]);

                        $datoscurso = $this->cursoController->storeImportCSV($request_curso);

                        $data = json_decode(json_encode($datoscurso, true), TRUE);
                        if ($data['original']['id_curso'] == 0) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Error al Importar, revise criterios de cursos'
                            ]);
                        }
                        $id_curso = $data['original']['id_curso'];
                    } else {
                        $id_curso = $datoscurso->id_curso;
                    }

                    $rut = $sheet->getCell('G' . $i)->getValue() . '' . $sheet->getCell('H' . $i)->getValue();
                    $correo = $sheet->getCell('P' . $i)->getValue() === ''
                        ? null
                        : $sheet->getCell('P' . $i)->getValue();
                    $request_matricula = new Request([
                        'fechaInscripcion'  => $sheet->getCell('U' . $i)->getValue(),
                        'tipoDocumento'     => 'RUT',
                        'rut'               => $rut,
                        'nombres'           => $sheet->getCell('J' . $i)->getValue(),
                        'primerApellido'    => $sheet->getCell('K' . $i)->getValue(),
                        'segundoApellido'   => $sheet->getCell('L' . $i)->getValue(),
                        'correo'            => $correo,
                        'fechaNacimiento'   => $fecha_nacimiento,
                        'idEstablecimiento' => $establecimiento->id,
                        'genero'            => $sheet->getCell('I' . $i)->getValue() == "M"
                            ? 'Masculino'
                            : 'Femenino',
                        'idCurso'           => $id_curso,
                        'estado'            => 'Activo',
                    ]);

                    $alumno = Alumno::where('rut', $rut)->get();
                    if (count($alumno) === 0) {
                        $datosmatricula = $this->store($request_matricula);
                    } else {
                        $datosmatricula = $this->update($request_matricula, $alumno[0]['id']);
                    }
                    $data = json_decode(json_encode($datosmatricula, true), TRUE);
                    if (!$data['original']['status'] == "success") {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Error al Importar, revise criterios de matriculas'
                        ]);
                    } else {
                        var_dump('i:', $i);
                        var_dump('data:', $data);
                    }
                    $i++;
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error al Importar, revise rbd de establecimiento'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Import Alumnos en excel.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function importAlumnos()
    {
        $sql = 'SELECT * FROM IMPORT_ALUMNOS';
        $alumnos_nuevos = DB::select($sql, []);
        // return response($alumnos_nuevos, 200);

        $nombre_curso = null;
        $letra_curso = null;
        $num_lista = 1;
        foreach ($alumnos_nuevos as $key => $nuevo) {
            $rut = $nuevo->rut . '' . $nuevo->dv;
            $alumno_encontrado = Alumno::select('*')
                ->where('rut', '=', $rut)
                ->first();
            if ($nuevo->nombre_grado === $nombre_curso && $nuevo->letra_curso === $letra_curso) {
                $num_lista++;
            } else {
                $nombre_curso = $nuevo->nombre_grado;
                $letra_curso = $nuevo->letra_curso;
                $num_lista = 1;
            }

            $idEstablecimiento = 2;
            $idPeriodo = 4;
            $tipo_ensenanza = $nuevo->tipo_ensenanza;
            $cod_grado = $nuevo->cod_grado;
            $letra_curso = $nuevo->letra_curso;
            $curso = Curso::getCursosAlumno($idPeriodo, $idEstablecimiento, $cod_grado, $tipo_ensenanza, $letra_curso);

            if ($alumno_encontrado) {
                // * EDITAR
                $fechaInscripcion = $nuevo->fecha_incorporacion;
                $numMatricula = null;
                $rut = $nuevo->rut . '' . $nuevo->dv;
                $tipoDocumento = substr($rut, 0, 3) === '100' ? 'IPE' : 'RUT';
                $nombres = $nuevo->nombres;
                $primerApellido = $nuevo->primer_apellido;
                $segundoApellido = $nuevo->segundo_apellido;
                $correo = $nuevo->email;

                if ($nuevo->genero === 'M') {
                    $genero = 'Masculino';
                } else if ($nuevo->genero === 'F') {
                    $genero = 'Femenino';
                } else {
                    $genero = 'Otro';
                }

                $fechaNacimiento = $nuevo->fecha_nacimiento;

                $numLista = $num_lista;
                $estado = 'Activo';
                $idCurso = $curso->id;
                $idEstablecimiento = $idEstablecimiento;

                $alumno = Alumno::findOrFail($alumno_encontrado->id);
                $alumno->fechaInscripcion = $fechaInscripcion;
                $alumno->numMatricula    = $numMatricula;
                $alumno->tipoDocumento   = $tipoDocumento;
                $alumno->nombres         = $nombres;
                $alumno->primerApellido  = $primerApellido;
                $alumno->segundoApellido = $segundoApellido;
                $alumno->correo          = $correo;
                $alumno->genero          = $genero;
                $alumno->fechaNacimiento = $fechaNacimiento;
                $alumno->numLista        = $numLista;
                $alumno->save();

                // buscar alumno en el curso en alumnos cursos
                // si lo encuentra
                $alumnos_cursos = new Alumnos_Cursos();
                $alumnos_cursos->idAlumno = $alumno->id;
                $alumnos_cursos->idCurso = $idCurso;
                $alumnos_cursos->estado  = $estado;
                $alumnos_cursos->save();
            } else {
                // * CREAR
                $fechaInscripcion = $nuevo->fecha_incorporacion;
                $numMatricula = null;
                // Si empieza en 100 es ipe si no es rut
                $rut = $nuevo->rut . '' . $nuevo->dv;
                $tipoDocumento = substr($rut, 0, 3) === '100' ? 'IPE' : 'RUT';
                $nombres = $nuevo->nombres;
                $primerApellido = $nuevo->primer_apellido;
                $segundoApellido = $nuevo->segundo_apellido;
                $correo = $nuevo->email;

                // si es M = Masculino, si es F = Femenino
                // $nuevo->genero
                if ($nuevo->genero === 'M') {
                    $genero = 'Masculino';
                } else if ($nuevo->genero === 'F') {
                    $genero = 'Femenino';
                } else {
                    $genero = 'Otro';
                }

                $fechaNacimiento = $nuevo->fecha_nacimiento;
                $paci = null;
                $pie = null;

                $numLista = $num_lista;
                $estado = 'Activo';
                $idDiagnostico = null;
                $idPrioritario = null;
                $idCurso = $curso->id;
                $idEstablecimiento = $idEstablecimiento;

                $alumno = new Alumno();
                $alumno->fechaInscripcion  = $fechaInscripcion;
                $alumno->numMatricula      = $numMatricula;
                $alumno->tipoDocumento     = $tipoDocumento;
                $alumno->rut               = $rut;
                $alumno->nombres           = $nombres;
                $alumno->primerApellido    = $primerApellido;
                $alumno->segundoApellido   = $segundoApellido;
                $alumno->correo            = $correo;
                $alumno->genero            = $genero;
                $alumno->fechaNacimiento   = $fechaNacimiento;
                $alumno->paci              = $paci;
                $alumno->pie               = $pie;
                $alumno->numLista          = $numLista;
                $alumno->idDiagnostico     = $idDiagnostico;
                $alumno->idPrioritario     = $idPrioritario;
                $alumno->idEstablecimiento = $idEstablecimiento;
                $alumno->save();

                $alumnos_cursos = new Alumnos_Cursos();
                $alumnos_cursos->idAlumno = $alumno->id;
                $alumnos_cursos->idCurso  = $idCurso;
                $alumnos_cursos->estado   = $estado;
                $alumnos_cursos->save();
            }
        }
        return response(1111, 200);



        // * ------------------------------------------------------
        // foreach ($alumnos_actuales as $key => $actual) {
        //     $rut = substr($actual->rut, 0, -1);
        //     $sql = 'SELECT * FROM IMPORT_ALUMNOS WHERE rut='. $rut ;
        //     $alumno_encontrado = DB::select($sql, []);
        //     if (!$alumno_encontrado) {
        //         $alumno_retirar = Alumno::findOrFail($actual->id);
        //         $alumno_retirar->estado = 'Retirado';
        //         $alumno_retirar->save();
        //     }
        // }
        // * ------------------------------------------------------
        // $sql = 'SELECT * FROM alumnos';
        // $alumnos = DB::select($sql, []);
        // foreach ($alumnos as $key => $alumno) {
        //     if (strlen($alumno->rut) > 10) {
        //     }
        // }
        // * ------------------------------------------------------
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            $alumno_curso = Alumnos_Cursos::where('idAlumno', $id);
            $alumno_curso->estado = 'Eliminado';
            $alumno_curso->save();
            // $alumno->delete();
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }
}
