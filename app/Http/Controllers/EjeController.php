<?php

namespace App\Http\Controllers;

use App\Models\Eje;
use App\Models\Objetivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EjeController extends Controller
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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getEjesAsignatura($idAsignatura)
    {
        return Eje::getEjesPorAsignatura($idAsignatura);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {

            DB::transaction(function () use ($request) {
                $idAsignatura = $request->input('idAsignatura');
                foreach ($request->input('ejes') as $key => $eje) {
                    $ejeCreado = Eje::Create([
                        'idAsignatura' => $idAsignatura,
                        'nombre' => $eje['nombre'],
                        'estado' => 'Activo',
                    ]);

                    foreach ($eje['objetivos'] as $key => $idObjetivo) {
                        $objetivo = Objetivo::findOrFail($idObjetivo);
                        $objetivo->idEje = $ejeCreado->id;
                        $objetivo->estado = 'Activo';
                        $objetivo->save();
                    }
                }

                return response('Success', 200);
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
