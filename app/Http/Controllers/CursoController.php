<?php

namespace App\Http\Controllers;

use App\Models\Curso;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Curso::getAll();
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
            'idPeriodo'         => 'required',
            'idGrado'           => 'required',
            'estado'            => 'required',
        ]);

        // try {

            // DB::transaction(function () use ($request) {

                $cantidad = intval($request->input('cantidad'));
                // $curso = new Curso();
                for ($i = 0; $i < $cantidad; $i++) {
                    $letra = '';
                    if ($i = 0) {
                        $letra = 'A';
                    } else if ($i = 1) {
                        $letra = 'B';
                    } else if ($i = 2) {
                        $letra = 'C';
                    } else if ($i = 3) {
                        $letra = 'D';
                    } else if ($i = 4) {
                        $letra = 'E';
                    }

                    // $curso->letra             = $letra;
                    // $curso->idEstablecimiento = $request->input('idEstablecimiento');
                    // $curso->idPeriodo         = $request->input('idPeriodo');
                    // $curso->idGrado           = $request->input('idGrado');
                    // $curso->estado            = $request->input('estado');
                    // $curso->save();

                    Curso::Create([
                        'letra'             => $letra,
                        'idEstablecimiento' => $request->input('idEstablecimiento'),
                        'idPeriodo'         => $request->input('idPeriodo'),
                        'idGrado'           => $request->input('idGrado'),
                        'estado'            => $request->input('estado'),
                    ]);
                    return response($i, 200);
                }

            //     return response(null, 200);
            // });

        // } catch (\Throwable $th) {
        //     return response($th, 500);
        // }
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
