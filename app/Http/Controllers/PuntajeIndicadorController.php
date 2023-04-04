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
    public function getPuntajesIndicadores($idPeriodo, $idCurso, $idAsignatura, $idObjetivo, $tipo)
    {
        $puntajeIndicador = PuntajeIndicador::getPuntajesIndicadores(
            $idPeriodo, $idCurso, $idAsignatura, $idObjetivo, $tipo
        );
        return $puntajeIndicador;
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
            $request->input('tipoIndicador'),
        );

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
