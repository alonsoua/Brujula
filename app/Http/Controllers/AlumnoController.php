<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\DiagnosticoPie;
use App\Models\Prioritario;

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
        return Alumno::getAll($user->idEstablecimientoActivo);
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
        //
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
