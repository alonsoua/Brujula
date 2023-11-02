<?php

namespace App\Http\Controllers;

use App\Models\Asignatura;
use App\Models\Objetivo;
use App\Models\UsuarioAsignatura;
use Illuminate\Http\Request;

class AsignaturaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $asignaturas = Asignatura::getAll();
        foreach ($asignaturas as $key => $asignatura) {
            $objetivos = Objetivo::getObjetivosAsignatura($asignatura->id);
            $asignatura->tieneEjes = false;
            $asignatura->objetivos = $objetivos;
            foreach ($objetivos as $key => $objetivo) {
                if (!is_null($objetivo->idEje) && $objetivo->estado == 'Activo') {
                    $asignatura->tieneEjes = true;
                }
            }
        }

        return $asignaturas;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getActivos()
    {
        $asignaturas = UsuarioAsignatura::getAsignaturaCursoActiva();

        return $asignaturas;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getActivosGrado($idgrado)
    {
        $asignaturas = Asignatura::getAllGrado($idgrado);

        return $asignaturas;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDocentesAsignaturas($idPeriodo)
    {
        $asignaturas = Asignatura::select(
            'asignaturas.id',
            'asignaturas.nombre as nombreAsignatura',
            'cursos.id as idCurso',
            'users.rut as rutDocente',
            'users.nombres',
            'users.primerApellido',
            'users.segundoApellido',
            'users.email'
        )
            ->leftJoin("cursos", "cursos.idGrado", "=", "asignaturas.idGrado")
            ->leftJoin("usuario_asignaturas", "asignaturas.id", "=", "usuario_asignaturas.idAsignatura")
            ->leftJoin("usuario_establecimientos", "usuario_asignaturas.idUsuarioEstablecimiento", "=", "usuario_establecimientos.id")
            ->leftJoin("users", "users.id", "=", "usuario_establecimientos.idUsuario")
            ->where('cursos.idPeriodo', $idPeriodo)
            ->where('asignaturas.estado', 'Activo')
            ->where('cursos.estado', 'Activo')
            ->where('cursos.estado', 'Activo')
            ->distinct('asignaturas.id')
            ->get();

        return $asignaturas;
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
