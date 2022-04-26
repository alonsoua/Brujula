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
    public function getIndicadorPersonalizadosAprobados($idObjetivo, $idPeriodo, $idCurso)
    {
        return IndicadorPersonalizado::getIndicadorPersonalizadosAprobados($idObjetivo, $idPeriodo, $idCurso);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndicadorPersonalizados($idObjetivo, $idPeriodo, $idCurso)
    {
        return IndicadorPersonalizado::getIndicadorPersonalizados($idObjetivo, $idPeriodo, $idCurso);
    }





    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'estado' => 'required',
        ]);

        try {
            DB::transaction(function () use ($request) {

                IndicadorPersonalizado::Create([
                    'nombre'     => $request->input('nombre'),
                    'idObjetivo' => $request->input('idObjetivo'),
                    'idCurso'  => $request->input('idCurso'),
                    'idPeriodo'  => $request->input('idPeriodo'),
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
