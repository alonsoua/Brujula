<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Establecimiento;
use App\Models\Alumno;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CursoController extends Controller
{
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

        return Curso::getAll($user->idEstablecimientoActivo, $idPeriodo);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getActivos(Request $request)
    {
        $user = $request->user();
        $idPeriodo = $user->idPeriodoActivo;
        if ($idPeriodo === null) {
            $establecimiento = Establecimiento::getAll($user->idEstablecimientoActivo);
            $idPeriodo = $establecimiento[0]['idPeriodoActivo'];
        }
        return Curso::getAllEstado($user->idEstablecimientoActivo, 'Activo', $idPeriodo);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getActivosEstablecimiento(Request $request, $idEstablecimiento)
    {
        $user = $request->user();
        $idPeriodo = $user->idPeriodoActivo;
        if ($idPeriodo === null) {
            $establecimiento = Establecimiento::getAll($user->idEstablecimientoActivo);
            $idPeriodo = $establecimiento[0]['idPeriodoActivo'];
        }

        return Curso::getAll($idEstablecimiento, $idPeriodo);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCursoMatricula($tipo, $grado, $letra, $idPeriodo, $idestablecimiento)
    {
        try {
            $curso = Curso::select('cursos.id as id_curso')
                ->join('grados', 'cursos.idGrado', '=', 'grados.id')
                ->where('grados.idNivel', '=', $tipo)
                ->where('grados.idGrado', '=', $grado)
                ->where('cursos.letra', '=', $letra)
                ->where('cursos.idPeriodo', '=', $idPeriodo)
                ->where('cursos.idEstablecimiento', '=', $idestablecimiento)
                ->where('cursos.estado', 'Activo')
                ->first();
            if ($curso != null) {
                return $curso;
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $request->validate([
            'idEstablecimiento' => 'required',
            'cantidad'          => 'required',
            'idGrado'           => 'required',
            'estado'            => 'required',
        ]);

        try {

            DB::transaction(function () use ($request) {

                $establecimiento = Establecimiento::getAll($request->idEstablecimiento);
                $idPeriodo = $establecimiento[0]['idPeriodoActivo'];
                $cantidad = intval($request->input('cantidad'));

                $letra = 'A';
                for ($i = 0; $i < $cantidad; $i++) {
                    Curso::Create([
                        'letra'             => $letra,
                        'idProfesorJefe'    => Null,
                        'idEstablecimiento' => $request->input('idEstablecimiento'),
                        'idGrado'           => $request->input('idGrado'),
                        'estado'            => $request->input('estado'),
                        'idPeriodo'         => $idPeriodo,
                    ]);
                    $letra++;
                }

                return response(null, 200);
            });
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeImportCSV(Request $request)
    {

        $request->validate([
            'letra'             => 'required',
            'idGrado'           => 'required',
            'idEstablecimiento' => 'required',
            'idPeriodo'         => 'required',
        ]);

        try {

            DB::transaction(function () use ($request) {
                Curso::Create([
                    'letra'             => $request->input('letra'),
                    'idProfesorJefe'    => Null,
                    'idGrado'           => $request->input('idGrado'),
                    'idEstablecimiento' => $request->input('idEstablecimiento'),
                    'idPeriodo'         => $request->input('idPeriodo'),
                    'estado'            => 'Activo',
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
        Request()->validate([
            'letra' => 'required',
        ]);

        try {
            $curso = Curso::findOrFail($id);

            $letra    = $request->input('letra');

            $curso->letra   = $letra;
            $curso->save();

            return response(null, 200);
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function ordenarLista(Request $request, $idCurso)
    {
        try {

            foreach ($request->input('lista') as $key => $lista_alumno) {
                $alumno = Alumno::findOrFail($lista_alumno['id']);
                $alumno->numLista = $lista_alumno['orden'];
                $alumno->save();
            }

            return response('success', 200);
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }
}
