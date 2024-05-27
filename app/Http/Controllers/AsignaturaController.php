<?php

namespace App\Http\Controllers;

use App\Models\Asignatura;
use App\Models\Curso;
use App\Models\User;
use App\Models\Establecimiento;
use App\Models\Periodo;
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
     * Obtiene las asignaturas asignadas al usuario
     * * $idUsuarioEstablecimiento
     * @return \Illuminate\Http\Response
     */
    public function getAsignaturasUsuario(Request $request, $idCurso, $idPeriodoHistorico)
    {
        $user = $request->user();
        // OBTIENE ID_PERIODO
        $id_periodo = $idPeriodoHistorico;
        if ($id_periodo === 'null') {
            $id_periodo = $user->idPeriodoActivo === null
                ? Establecimiento::getIDPeriodoActivo($user->idEstablecimientoActivo)
                : $user->idPeriodoActivo;
        }
        // $nombre_periodo = Periodo::where('id', $id_periodo)->value('nombre');
        $id_usuario_establecimiento = null;
        if (($user->rolActivo === 'Docente'
        || $user->rolActivo === 'Docente Pie'
        || $user->rolActivo === 'Asistente')) {
            $id_usuario_establecimiento = User::getUsuarioEstablecimiento($user->id, $user->idEstablecimientoActivo);
        }

        $cursos = Curso::select(
               'asignaturas.id',
                'asignaturas.nombre',
                'asignaturas.idGrado',
                'cursos.id as idCurso'
            );
            if (!is_null($id_usuario_establecimiento)) {
                $cursos = $cursos->leftJoin("usuario_asignaturas", "usuario_asignaturas.idCurso", "=", "cursos.id");
            }

            $cursos = $cursos->leftJoin('asignaturas', function ($join) use ($id_usuario_establecimiento) {
                if (!is_null($id_usuario_establecimiento)) {
                    $join->on('asignaturas.id', '=', 'usuario_asignaturas.idAsignatura');
                }
                $join->on('asignaturas.idGrado', '=', 'cursos.idGrado');
            });

        if (!is_null($id_usuario_establecimiento)) {
            $cursos = $cursos->where('usuario_asignaturas.idUsuarioEstablecimiento', $id_usuario_establecimiento);
        }
        $cursos = $cursos->where('cursos.estado', 'Activo')
            ->where('asignaturas.estado', 'Activo')
            ->where('cursos.idPeriodo', $id_periodo)
            ->where('cursos.id', $idCurso)
            ->orderBy('asignaturas.id')
            ->distinct()
            ->get();
        return $cursos;
    }

    /**
     * Obtiene las asignaturas asignadas al usuario
     * * $idUsuarioEstablecimiento
     * @return \Illuminate\Http\Response
     */
    public function getAsignaturasCurso(Request $request, $idCurso, $idPeriodoHistorico)
    {
        $user = $request->user();
        // OBTIENE ID_PERIODO
        $id_periodo = $idPeriodoHistorico;
        if ($id_periodo === 'null') {
            $id_periodo = $user->idPeriodoActivo === null
                ? Establecimiento::getIDPeriodoActivo($user->idEstablecimientoActivo)
                : $user->idPeriodoActivo;
        }

        $cursos = Curso::select(
               'asignaturas.id',
                'asignaturas.nombre',
                'asignaturas.idGrado',
                'cursos.id as idCurso'
            );

            $cursos = $cursos->leftJoin('asignaturas', function ($join) {
                $join->on('asignaturas.idGrado', '=', 'cursos.idGrado');
            });
        $cursos = $cursos->where('cursos.estado', 'Activo')
            ->where('asignaturas.estado', 'Activo')
            ->where('cursos.idPeriodo', $id_periodo)
            ->where('cursos.id', $idCurso)
            ->orderBy('asignaturas.id')
            ->distinct()
            ->get();
        return $cursos;
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
