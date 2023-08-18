<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\DiagnosticoPie;
use App\Models\Prioritario;
use App\Models\Establecimiento;
use App\Models\Curso;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AlumnoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        return Alumno::getAll($user->idEstablecimientoActivo, null);
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

    public function getAlumnosCurso($idCurso) {
        return Alumno::select(
            'alumnos.*'
            , 'prioritarios.nombre as nombrePrioritario'
            , 'diagnosticos_pie.nombre as nombreDiagnostico'
            , 'diagnosticos_pie.abreviatura as abreviaturaDiagnostico'
            , 'diagnosticos_pie.tipoNee as tipoNee'
        )
        ->leftJoin("prioritarios", "alumnos.idPrioritario", "=", "prioritarios.id")
        ->leftJoin("diagnosticos_pie", "alumnos.idDiagnostico", "=", "diagnosticos_pie.id")
        ->where('alumnos.idCurso', $idCurso)
        ->where('alumnos.estado', 'Activo')
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
            'idEstablecimiento' => 'required',
            'idCurso' => 'required',
            'tipoDocumento' => 'required|max:4',
            'rut' => 'required|max:15|unique:alumnos',
            'nombres' => 'required|max:250',
            'primerApellido' => 'required|max:250',
            'segundoApellido' => 'required|max:250',
            'genero' => 'required|max:10',
            'fechaNacimiento' => 'required|max:250',
            'estado' => 'required',
        ]);

        try {
            DB::transaction(function () use ($request) {

                $fechaInscripcion = date('Y-m-d H:i:s');

                $idCurso           = $request->input('idCurso');
                $idEstablecimiento = $request->input('idEstablecimiento');
                $alumnos =  Alumno::getAlumnosCursoEstablecimiento(
                                  $idCurso
                                , $idEstablecimiento
                            );
                $numLista = count($alumnos) + 1;
                $pie = $request->input('pie');

                $diagnosticoPie = !is_null($pie)
                    ? $request->input('idDiagnostico')
                    : null;

                Alumno::Create([
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
                    'estado'            => $request->input('estado'),
                    'idDiagnostico'     => $diagnosticoPie,
                    'idPrioritario'     => $request->input('idPrioritario'),
                    'idCurso'           => $idCurso,
                    'idEstablecimiento' => $idEstablecimiento,
                ]);

                return response(null, 200);
            });

        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
            'rut' => 'required|max:15|unique:alumnos,rut,'.$id.',id' ,
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
            $alumno->idCurso         = $idCurso;
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
            $alumno->estado          = $estado;
            $alumno->save();

            return response(null, 200);

        } catch (\Throwable $th) {
            return response($th, 500);
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
            $rut = $nuevo->rut.''.$nuevo->dv;
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
                $fechaInscripcion = $nuevo->fecha_incorporacion;
                $numMatricula = null;
                $rut = $nuevo->rut.''.$nuevo->dv;
                $tipoDocumento = substr($rut, 0, 3) === '100' ? 'IPE' : 'RUT';
                $nombres = $nuevo->nombres;
                $primerApellido = $nuevo->primer_apellido;
                $segundoApellido = $nuevo->segundo_apellido;
                $correo = $nuevo->email;

                if ($nuevo->genero === 'M') {
                    $genero = 'Masculino';
                }
                else if ($nuevo->genero === 'F') {
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
                $alumno->estado          = $estado;
                $alumno->idCurso         = $idCurso;
                $alumno->save();
            } else {
                // CREAR


                $fechaInscripcion = $nuevo->fecha_incorporacion;
                $numMatricula = null;
                // Si empieza en 100 es ipe si no es rut
                $rut = $nuevo->rut.''.$nuevo->dv;
                $tipoDocumento = substr($rut, 0, 3) === '100' ? 'IPE' : 'RUT';
                $nombres = $nuevo->nombres;
                $primerApellido = $nuevo->primer_apellido;
                $segundoApellido = $nuevo->segundo_apellido;
                $correo = $nuevo->email;

                // si es M = Masculino, si es F = Femenino
                // $nuevo->genero
                if ($nuevo->genero === 'M') {
                    $genero = 'Masculino';
                }
                else if ($nuevo->genero === 'F') {
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
                $alumno->estado            = $estado;
                $alumno->idDiagnostico     = $idDiagnostico;
                $alumno->idPrioritario     = $idPrioritario;
                $alumno->idCurso           = $idCurso;
                $alumno->idEstablecimiento = $idEstablecimiento;
                $alumno->save();
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
        //         var_dump('<pre>');
        //         var_dump('- rut: ', $alumno->rut);
        //         var_dump('</pre>');
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
            $alumno = Alumno::findOrFail($id);
            $alumno->delete();
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }
}
