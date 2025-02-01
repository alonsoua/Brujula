<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Alumnos_Cursos;
use App\Models\Curso;

use App\Models\Master\DiagnosticoPie;
use App\Models\Master\Establecimiento;
use App\Models\Master\Grado;
use App\Models\Master\Prioritario;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AlumnoController extends Controller
{
    protected $establecimientoController;
    protected $cursoController;

    public function __construct()
    {
        $this->establecimientoController = app('App\Http\Controllers\Master\EstablecimientoController');
        $this->cursoController = app('App\Http\Controllers\CursoController');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user()->getUserData();
        $idPeriodo = $user['periodo']['id'];

        // 🔹 Obtener alumnos con sus cursos desde la conexión 'establecimiento'
        $alumnos = Alumno::on('establecimiento')
        ->select(
            'alumnos.*',
            'alumnos_cursos.idCurso',
            'alumnos_cursos.estado',
            'cursos.nombre as nombreCurso',
            'cursos.letra',
            'cursos.idGrado',
        )
            ->leftJoin("alumnos_cursos", "alumnos.id", "=", "alumnos_cursos.idAlumno")
            ->leftJoin("cursos", "alumnos_cursos.idCurso", "=", "cursos.id")
            ->where('cursos.idPeriodo', $idPeriodo)
            ->where('alumnos_cursos.estado', '!=', 'Eliminado')
            ->orderBy('cursos.idGrado')
            ->orderBy('cursos.letra')
            ->orderBy('alumnos.numLista')
            ->get();

        // 🔹 Obtener diagnósticos desde la conexión 'master' y asociarlos por ID
        $diagnosticos = DiagnosticoPie::on('master')
        ->whereIn('id', $alumnos->pluck('idDiagnostico')->unique()->filter())
        ->get()
            ->keyBy('id');

        // 🔹 Asociar los datos del diagnóstico directamente a cada alumno
        $alumnos->each(function ($alumno) use ($diagnosticos) {
            $diagnostico = $diagnosticos[$alumno->idDiagnostico] ?? null;
            $alumno->nombreDiagnostico = $diagnostico->nombre ?? null;
            $alumno->tipoNee = $diagnostico->tipoNee ?? null;
            $alumno->abreviatura = $diagnostico->abreviatura ?? null;
        });


        return $alumnos;
    }

    // public function getAlumnosPeriodo(Request $request)
    // {
    //     $user = $request->user();
    //     $idPeriodo = $user->idPeriodoActivo;
    //     if ($idPeriodo === null) {
    //         $establecimiento = Establecimiento::getAll($user->idEstablecimientoActivo);
    //         $idPeriodo = $establecimiento[0]['idPeriodoActivo'];
    //     }
    //     return Alumno::getAll($user->idEstablecimientoActivo, $idPeriodo);
    // }

    // public function getAlumnosCurso($idCurso)
    // {
    //     return Alumno::select(
    //         'alumnos.*',
    //         'alumnos_cursos.idCurso',
    //         'alumnos_cursos.estado',
    //         'prioritarios.nombre as nombrePrioritario',
    //         'diagnosticos_pie.nombre as nombreDiagnostico',
    //         'diagnosticos_pie.abreviatura as abreviaturaDiagnostico',
    //         'diagnosticos_pie.tipoNee as tipoNee'
    //     )
    //         ->leftJoin("prioritarios", "alumnos.idPrioritario", "=", "prioritarios.id")
    //         ->leftJoin("diagnosticos_pie", "alumnos.idDiagnostico", "=", "diagnosticos_pie.id")
    //         ->leftJoin("alumnos_cursos", "alumnos.id", "=", "alumnos_cursos.idAlumno")
    //         ->where('alumnos_cursos.idCurso', $idCurso)
    //         ->where('alumnos_cursos.estado', 'Activo')
    //         ->orderBy('alumnos.numLista')
    //         ->get();
    // }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {

            $request->validate([
                'tipoDocumento' => 'required|max:4',
                'rut' => 'required|max:15|unique:alumnos',
                'nombres' => 'required|max:250',
                'primerApellido' => 'required|max:250',
                'segundoApellido' => 'required|max:250',
                'fechaNacimiento' => 'required|date',
                'genero' => 'required|max:10',
                'idCurso' => 'required|integer',
            ]);
            
            DB::transaction(function () use ($request) {

                $fechaInscripcion = date('Y-m-d H:i:s');
                $idCurso = $request->input('idCurso');

                $numLista = Alumno::getSiguienteNumLista($idCurso);
                
                $pie = $request->input('pie');

                $diagnosticoPie = !is_null($pie)
                    ? $request->input('idDiagnostico')
                    : null;

                $nombrePrioritario = Prioritario::find($request->input('idPrioritario'));

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
                    'pie'               => $pie ?? null,
                    'numLista'          => $numLista,
                    'idDiagnostico'     => $diagnosticoPie,
                    'idPrioritario'     => $request->input('idPrioritario'),
                    'nombre_prioritario' => $nombrePrioritario,
                ]);


                Alumnos_Cursos::Create([
                    'idAlumno' => $alumno->id,
                    'idCurso'  => $idCurso,
                    'estado'   => 'Activo',
                ]);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Alumno creado con éxito.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(), // Aquí se retornan los detalles de los errores
            ], 422);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $idAlumno)
    {
        try {
            $request->validate([
                'idCurso' => 'required',
                'rut' => 'required|max:15|unique:alumnos,rut,' . $idAlumno . ',id',
                'tipoDocumento' => 'required|max:4',
                'nombres' => 'required|max:250',
                'primerApellido' => 'required|max:250',
                'segundoApellido' => 'required|max:250',
                'genero' => 'required|max:10',
                'fechaNacimiento' => 'required|max:250',
            ]);
            $alumno = Alumno::findOrFail($idAlumno);


            $idCurso          = $request->input('idCurso');
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
            $nombrePrioritario = Prioritario::find($idPrioritario);

            if ($paci == 0) {
                $paci = null;
            }
            if ($pie == 0) {
                $pie = null;
            }

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
            $alumno->nombre_prioritario = $nombrePrioritario;
            
            if ($alumno->save()) {
                
                $existeCurso = Alumnos_Cursos::where('idAlumno', $idAlumno)
                    ->where('idCurso', $idCurso)
                    ->where('estado', 'Activo')
                    ->exists();
                // Verifica si el curso es nuevo
                if (!$existeCurso) {

                    // Cambia curso anterior a inactivo
                    $id_periodo = Curso::select('cursos.idPeriodo')
                        ->where('cursos.id', $idCurso)
                        ->get()
                        ->first();
                    $curso_anterior = Curso::select('alumnos_cursos.id')
                        ->where('cursos.idPeriodo', '=', $id_periodo->idPeriodo)
                        ->leftJoin("alumnos_cursos", "alumnos_cursos.idCurso", "=", "cursos.id")
                        ->where('alumnos_cursos.idAlumno', $idAlumno)
                        ->where('alumnos_cursos.estado', 'Activo')
                        ->get()
                        ->first();
                    if (!empty($curso_anterior)) {
                        $alumno_c = Alumnos_Cursos::findOrFail($curso_anterior->id);
                        $alumno_c->estado = 'Retirado';
                        $alumno_c->save();
                    }

                    // Crea el nuevo curso
                    Alumnos_Cursos::Create([
                        'idAlumno' => $idAlumno,
                        'idCurso'  => $idCurso,
                        'estado'   => 'Activo',
                    ]);
                }
            }

            return response()->json(['status' => 'success',
                'message' => 'Alumno editado con éxito.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(), // Aquí se retornan los detalles de los errores
            ], 422);
        }
    }


    public function importAlumnosCSV(Request $request)
    {
        try {
            logger()->info('Inicio de importación de alumnos desde CSV');

            // 📌 1️⃣ Procesar el archivo CSV
            $documento = $request->file('lista');
            $name = time() . '.' . $documento->getClientOriginalExtension();
            $destinationPath = storage_path('/app/Imports');
            $documento->move($destinationPath, $name);

            $FileName = storage_path('/app/Imports') . "/" . $name;
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $spreadsheet = $reader->load($FileName);
            $sheet = $spreadsheet->getSheet(0);

            // 📌 2️⃣ Recorrer filas del CSV
            $i = 2; // Inicia en fila 2 para omitir encabezados
            foreach ($sheet->getRowIterator() as $row) {
                $user = $request->user()->getUserData();
                $periodoExcel = $sheet->getCell('A' . $i)->getValue();

                // 📌 Validar que el periodo en el archivo coincide con el periodo activo
                if ($user['periodo']['nombre'] != $periodoExcel) {
                    logger()->error("Periodo no coincide en la fila $i", ['archivo' => $periodoExcel, 'sistema' => $user['periodo']['nombre']]);
                    return response()->json(['status' => 'error', 'message' => 'El periodo no coincide']);
                }

                // 📌 3️⃣ Obtener ID del grado
                $codGradoExcel = $sheet->getCell('D' . $i)->getValue();
                $idNivelExcel = $sheet->getCell('C' . $i)->getValue();
                $idGrado = Grado::where('idGrado', $codGradoExcel)
                    ->where('idNivel', $idNivelExcel)
                    ->value('id');

                // 📌 4️⃣ Obtener o crear el curso
                $letraExcel = $sheet->getCell('F' . $i)->getValue();
                $id_curso = $this->cursoController->getCursoImportCSV($idGrado, $letraExcel, $user['periodo']['id']);
                logger()->info("Curso obtenido o creado", ['idCurso' => $id_curso, 'grado' => $idGrado, 'letra' => $letraExcel]);

                // 📌 5️⃣ Obtener información del alumno
                $rut = $sheet->getCell('G' . $i)->getValue() . '' . $sheet->getCell('H' . $i)->getValue();
                $correo = $sheet->getCell('P' . $i)->getValue() ?: null;
                $fecha_nacimiento = $sheet->getCell('S' . $i)->getValue();

                $alumnoData = [
                    'fechaInscripcion'  => $sheet->getCell('U' . $i)->getValue(),
                    'tipoDocumento'     => 'RUT',
                    'rut'               => $rut,
                    'nombres'           => $sheet->getCell('J' . $i)->getValue(),
                    'primerApellido'    => $sheet->getCell('K' . $i)->getValue(),
                    'segundoApellido'   => $sheet->getCell('L' . $i)->getValue(),
                    'correo'            => $correo,
                    'fechaNacimiento'   => $fecha_nacimiento,
                    'genero'            => $sheet->getCell('I' . $i)->getValue() == "M" ? 'Masculino' : 'Femenino',
                    'idCurso'           => $id_curso,
                ];

                // 📌 6️⃣ Verificar si el alumno ya existe
                $alumno = Alumno::where('rut', $rut)->first();
                if (!$alumno) {
                    logger()->info("Creando nuevo alumno", ['rut' => $rut, 'nombre' => $alumnoData['nombres']]);
                    $datosmatricula = $this->store(new Request($alumnoData));
                } else {
                    logger()->info("Actualizando alumno existente", ['rut' => $rut, 'idAlumno' => $alumno->id]);
                    $datosmatricula = $this->update(new Request($alumnoData), $alumno->id);
                }

                // 📌 7️⃣ Verificar resultado de la operación
                $data = json_decode(json_encode($datosmatricula, true), TRUE);
                if ($data['original']['status'] == "success") {
                    logger()->info("Importación exitosa del alumno", ['rut' => $rut, 'fila' => $i]);
                } else {
                    logger()->error("Error al importar alumno en fila $i", ['rut' => $rut, 'mensaje' => $data['original']['message']]);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Error al Importar, revise criterios de matrículas'
                    ]);
                }

                $i++; // Incrementar fila
            }

            logger()->info("Importación finalizada correctamente");
            return response()->json(['status' => 'success', 'message' => 'Importación completada']);
        } catch (\Exception $e) {
            logger()->error("Error general en la importación", ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Import Alumnos en excel.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function importAlumnos()
    // {
    //     $sql = 'SELECT * FROM IMPORT_ALUMNOS';
    //     $alumnos_nuevos = DB::select($sql, []);
    //     // return response($alumnos_nuevos, 200);

    //     $nombre_curso = null;
    //     $letra_curso = null;
    //     $num_lista = 1;
    //     foreach ($alumnos_nuevos as $key => $nuevo) {
    //         $rut = $nuevo->rut . '' . $nuevo->dv;
    //         $alumno_encontrado = Alumno::select('*')
    //             ->where('rut', '=', $rut)
    //             ->first();
    //         if ($nuevo->nombre_grado === $nombre_curso && $nuevo->letra_curso === $letra_curso) {
    //             $num_lista++;
    //         } else {
    //             $nombre_curso = $nuevo->nombre_grado;
    //             $letra_curso = $nuevo->letra_curso;
    //             $num_lista = 1;
    //         }

    //         $idEstablecimiento = 2;
    //         $idPeriodo = 4;
    //         $tipo_ensenanza = $nuevo->tipo_ensenanza;
    //         $cod_grado = $nuevo->cod_grado;
    //         $letra_curso = $nuevo->letra_curso;
    //         $curso = Curso::getCursosAlumno($idPeriodo, $idEstablecimiento, $cod_grado, $tipo_ensenanza, $letra_curso);

    //         if ($alumno_encontrado) {
    //             // * EDITAR
    //             $fechaInscripcion = $nuevo->fecha_incorporacion;
    //             $numMatricula = null;
    //             $rut = $nuevo->rut . '' . $nuevo->dv;
    //             $tipoDocumento = substr($rut, 0, 3) === '100' ? 'IPE' : 'RUT';
    //             $nombres = $nuevo->nombres;
    //             $primerApellido = $nuevo->primer_apellido;
    //             $segundoApellido = $nuevo->segundo_apellido;
    //             $correo = $nuevo->email;

    //             if ($nuevo->genero === 'M') {
    //                 $genero = 'Masculino';
    //             } else if ($nuevo->genero === 'F') {
    //                 $genero = 'Femenino';
    //             } else {
    //                 $genero = 'Otro';
    //             }

    //             $fechaNacimiento = $nuevo->fecha_nacimiento;

    //             $numLista = $num_lista;
    //             $estado = 'Activo';
    //             $idCurso = $curso->id;
    //             $idEstablecimiento = $idEstablecimiento;

    //             $alumno = Alumno::findOrFail($alumno_encontrado->id);
    //             $alumno->fechaInscripcion = $fechaInscripcion;
    //             $alumno->numMatricula    = $numMatricula;
    //             $alumno->tipoDocumento   = $tipoDocumento;
    //             $alumno->nombres         = $nombres;
    //             $alumno->primerApellido  = $primerApellido;
    //             $alumno->segundoApellido = $segundoApellido;
    //             $alumno->correo          = $correo;
    //             $alumno->genero          = $genero;
    //             $alumno->fechaNacimiento = $fechaNacimiento;
    //             $alumno->numLista        = $numLista;
    //             $alumno->save();

    //             // buscar alumno en el curso en alumnos cursos
    //             // si lo encuentra
    //             $alumnos_cursos = new Alumnos_Cursos();
    //             $alumnos_cursos->idAlumno = $alumno->id;
    //             $alumnos_cursos->idCurso = $idCurso;
    //             $alumnos_cursos->estado  = $estado;
    //             $alumnos_cursos->save();
    //         } else {
    //             // * CREAR
    //             $fechaInscripcion = $nuevo->fecha_incorporacion;
    //             $numMatricula = null;
    //             // Si empieza en 100 es ipe si no es rut
    //             $rut = $nuevo->rut . '' . $nuevo->dv;
    //             $tipoDocumento = substr($rut, 0, 3) === '100' ? 'IPE' : 'RUT';
    //             $nombres = $nuevo->nombres;
    //             $primerApellido = $nuevo->primer_apellido;
    //             $segundoApellido = $nuevo->segundo_apellido;
    //             $correo = $nuevo->email;

    //             // si es M = Masculino, si es F = Femenino
    //             // $nuevo->genero
    //             if ($nuevo->genero === 'M') {
    //                 $genero = 'Masculino';
    //             } else if ($nuevo->genero === 'F') {
    //                 $genero = 'Femenino';
    //             } else {
    //                 $genero = 'Otro';
    //             }

    //             $fechaNacimiento = $nuevo->fecha_nacimiento;
    //             $paci = null;
    //             $pie = null;

    //             $numLista = $num_lista;
    //             $estado = 'Activo';
    //             $idDiagnostico = null;
    //             $idPrioritario = null;
    //             $idCurso = $curso->id;
    //             $idEstablecimiento = $idEstablecimiento;

    //             $alumno = new Alumno();
    //             $alumno->fechaInscripcion  = $fechaInscripcion;
    //             $alumno->numMatricula      = $numMatricula;
    //             $alumno->tipoDocumento     = $tipoDocumento;
    //             $alumno->rut               = $rut;
    //             $alumno->nombres           = $nombres;
    //             $alumno->primerApellido    = $primerApellido;
    //             $alumno->segundoApellido   = $segundoApellido;
    //             $alumno->correo            = $correo;
    //             $alumno->genero            = $genero;
    //             $alumno->fechaNacimiento   = $fechaNacimiento;
    //             $alumno->paci              = $paci;
    //             $alumno->pie               = $pie;
    //             $alumno->numLista          = $numLista;
    //             $alumno->idDiagnostico     = $idDiagnostico;
    //             $alumno->idPrioritario     = $idPrioritario;
    //             $alumno->idEstablecimiento = $idEstablecimiento;
    //             $alumno->save();

    //             $alumnos_cursos = new Alumnos_Cursos();
    //             $alumnos_cursos->idAlumno = $alumno->id;
    //             $alumnos_cursos->idCurso  = $idCurso;
    //             $alumnos_cursos->estado   = $estado;
    //             $alumnos_cursos->save();
    //         }
    //     }
    //     return response(1111, 200);



    //     // * ------------------------------------------------------
    //     // foreach ($alumnos_actuales as $key => $actual) {
    //     //     $rut = substr($actual->rut, 0, -1);
    //     //     $sql = 'SELECT * FROM IMPORT_ALUMNOS WHERE rut='. $rut ;
    //     //     $alumno_encontrado = DB::select($sql, []);
    //     //     if (!$alumno_encontrado) {
    //     //         $alumno_retirar = Alumno::findOrFail($actual->id);
    //     //         $alumno_retirar->estado = 'Retirado';
    //     //         $alumno_retirar->save();
    //     //     }
    //     // }
    //     // * ------------------------------------------------------
    //     // $sql = 'SELECT * FROM alumnos';
    //     // $alumnos = DB::select($sql, []);
    //     // foreach ($alumnos as $key => $alumno) {
    //     //     if (strlen($alumno->rut) > 10) {
    //     //     }
    //     // }
    //     // * ------------------------------------------------------
    // }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            // Buscar la relación del alumno en alumnos_cursos
            $alumnoCurso = DB::connection('establecimiento')
            ->table('alumnos_cursos')
            ->where('idAlumno', $id)
                ->first();

            if (!$alumnoCurso) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No se encontró la relación del alumno con el curso',
                ], 404);
            }

            // Actualizar el estado en la tabla alumnos_cursos
            DB::connection('establecimiento')
            ->table('alumnos_cursos')
            ->where('idAlumno', $id)
                ->update(['estado' => 'Eliminado']); // 👈 Cambia el estado a "Eliminado"

            return response()->json([
                'status'  => 'success',
                'message' => 'El alumno ha sido marcado como eliminado',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al actualizar el estado del alumno: ' . $e->getMessage(),
            ], 500);
        }
    }
}
