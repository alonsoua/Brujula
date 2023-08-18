<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\IndicadorPersonalizado;
use App\Models\PuntajeIndicador;


class IndicadorPersonalizadoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndicadorPersonalizadosAprobados($idObjetivo, $idPeriodo, $idCurso, $tipo)
    {
        // return  DB::select(
        //     'SELECT
        //         ip.id
        //         , ip.nombre
        //         , ip.idUsuario_created
        //         , ip.idUsuario_updated
        //         , ip.estado
        //         , ip.created_at
        //         , ip.updated_at
        //     FROM indicador_personalizados as ip
        //     WHERE
        //         ip.idObjetivo = ' . $idObjetivo . ' AND
        //         ip.idPeriodo = ' . $idPeriodo . ' AND
        //         ip.idCurso = ' . $idCurso . ' AND
        //         ip.tipo_objetivo = ' . $tipo . ' AND
        //         ip.estado = "Aprobado"
        //     '
        // );
        // return IndicadorPersonalizado::selectRaw('
        //         indicador_personalizados.id
        //     , indicador_personalizados.nombre
        //     , indicador_personalizados.idUsuario_created
        //     , indicador_personalizados.idUsuario_updated
        //     , indicador_personalizados.estado
        //     , indicador_personalizados.created_at
        //     , indicador_personalizados.updated_at
        // ')
        //     ->where('indicador_personalizados.idObjetivo', $idObjetivo)
        //     ->where('indicador_personalizados.idPeriodo', $idPeriodo)
        //     ->where('indicador_personalizados.idCurso', $idCurso)
        //     ->where('indicador_personalizados.tipo_objetivo', $tipo)
        //     ->where('indicador_personalizados.estado', 'Aprobado')
        //     ->get();
        return IndicadorPersonalizado::getIndicadorPersonalizadosAprobados($idObjetivo, $idPeriodo, $idCurso, $tipo);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndicadorPersonalizados($idObjetivo, $idPeriodo, $idCurso, $tipo)
    {
        return IndicadorPersonalizado::getIndicadorPersonalizados($idObjetivo, $idPeriodo, $idCurso, $tipo);
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
            'nombre' => 'required',
            'idUsuario' => 'required',
            'idObjetivo' => 'required',
            'idCurso' => 'required',
            'idPeriodo' => 'required',
            'tipo_objetivo' => 'required',
            'estado' => 'required',
        ]);

        try {
            DB::transaction(function () use ($request) {

                IndicadorPersonalizado::Create([
                    'nombre'     => $request->input('nombre'),
                    'idObjetivo' => $request->input('idObjetivo'),
                    'idCurso'  => $request->input('idCurso'),
                    'idPeriodo'  => $request->input('idPeriodo'),
                    'tipo_objetivo'  => $request->input('tipo_objetivo'),
                    'estado'     => $request->input('estado'),
                    'idUsuario_created'  => $request->input('idUsuario'),
                    'idUsuario_updated'  => $request->input('idUsuario'),
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
            'nombre' => 'required',
        ]);

        try {
            $indicador = IndicadorPersonalizado::findOrFail($id);

            $nombre    = $request->input('nombre');

            $indicador->nombre   = $nombre;
            $indicador->save();

            return response(null, 200);
        } catch (\Throwable $th) {
            return response($th, 500);
        }
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
            $indicador = IndicadorPersonalizado::findOrFail($id);

            $puntajesIndicador = PuntajeIndicador::findIndicadorPersonalizados($id);

            foreach ($puntajesIndicador as $key => $pi) {
                $puntajeIndicador = PuntajeIndicador::findOrFail($pi->id);
                $puntajeIndicador->estado = 'Inactivo';
                $puntajeIndicador->save();
            }
            $indicador->estado   = 'Eliminado';
            $indicador->save();
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }
}
