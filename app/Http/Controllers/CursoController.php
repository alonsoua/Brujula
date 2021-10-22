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
            'idGrado'           => 'required',
            'estado'            => 'required',
        ]);

        try {

            DB::transaction(function () use ($request) {

                $cantidad = intval($request->input('cantidad'));
                $letra = 'A';
                for ($i = 0; $i < $cantidad; $i++) {
                    Curso::Create([
                        'letra'             => $letra,
                        'idProfesorJefe'    => Null,
                        'idEstablecimiento' => $request->input('idEstablecimiento'),
                        'idGrado'           => $request->input('idGrado'),
                        'estado'            => $request->input('estado'),
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
