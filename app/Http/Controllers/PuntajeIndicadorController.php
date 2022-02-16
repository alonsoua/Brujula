<?php

namespace App\Http\Controllers;

use App\Models\PuntajeIndicador;
use App\Models\PuntajeIndicadorTransformacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PuntajeIndicadorController extends Controller
{
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
     * @return \Illuminate\Http\Response
     */
    public function getPuntajesIndicadores($idPeriodo, $idCurso, $idAsignatura, $idObjetivo)
    {
        return PuntajeIndicador::getPuntajesIndicadores(
            $idPeriodo, $idCurso, $idAsignatura, $idObjetivo
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getPuntajesIndicadoresTransformacion()
    {
        return PuntajeIndicadorTransformacion::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
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
    public function update(Request $request, $puntaje)
    {
        // buscar el puntaje

        $puntajeIndicador = PuntajeIndicador::findPuntajeIndicador(
            $request->input('idPeriodo'),
            $request->input('idCurso'),
            $request->input('idAsignatura'),
            $request->input('idIndicador'),
            $request->input('idAlumno'),
        );

        $id = $request;

        $user = $request->user();
        if (is_null($request->input('puntaje'))) {
            try {
                $id = $puntajeIndicador[0]['id'];
                $PuntajeIndicador = PuntajeIndicador::findOrFail($id);
                $PuntajeIndicador->delete();
                return response('success', 200);
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

                return response('success', 200);
            } catch (\Throwable $th) {
                return response($th, 500);
            }

        } else {

            try {
                $usuarioCreate = $user->id;
                $PuntajeIndicador = PuntajeIndicador::Create([
                    'idPeriodo'         => $request->input('idPeriodo'),
                    'idCurso'           => $request->input('idCurso'),
                    'idAsignatura'      => $request->input('idAsignatura'),
                    'idIndicador'       => $request->input('idIndicador'),
                    'idAlumno'          => $request->input('idAlumno'),
                    'puntaje'           => $request->input('puntaje'),
                    'estado'            => $request->input('estado'),
                    'idUsuario_created' => $usuarioCreate,
                ]);

                return response($PuntajeIndicador, 200);
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
