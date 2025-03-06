<?php

namespace App\Http\Controllers;

use App\Models\Master\Asignatura;
use App\Models\Curso;
use App\Models\Master\Establecimiento;
use Illuminate\Http\Request;

class AsignaturaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function index()
    // {
    //     $asignaturas = Asignatura::getAll();
    //     foreach ($asignaturas as $key => $asignatura) {
    //         $objetivos = Objetivo::getObjetivosAsignatura($asignatura->id);
    //         $asignatura->tieneEjes = false;
    //         $asignatura->objetivos = $objetivos;
    //         foreach ($objetivos as $key => $objetivo) {
    //             if (!is_null($objetivo->idEje) && $objetivo->estado == 'Activo') {
    //                 $asignatura->tieneEjes = true;
    //             }
    //         }
    //     }

    //     return $asignaturas;
    // }

    /**
     * Obtiene las asignaturas asignadas al usuario
     * * $idUsuarioEstablecimiento
     * @return \Illuminate\Http\Response
     */
    public function getAsignaturasUsuario(Request $request, $idCurso, $idPeriodoHistorico)
    {

        $user = $request->user()->getUserData();
        $idEstabUsuarioRol = $user['rolActivo']['idEstabUsuarioRol'];
        $idRol = $user['rolActivo']['id'];
        $idPeriodo = $idPeriodoHistorico === 'null' || $idPeriodoHistorico === 'undefined'
        ? $user['periodo']['id']
            : $idPeriodoHistorico;

        // 🔹 1️⃣ Obtener cursos desde establecimiento
        $cursos = Curso::select('cursos.id', 'cursos.idGrado');
        if ($idRol === 7) {
            $cursos->when(!is_null($idEstabUsuarioRol), function ($query) use ($idEstabUsuarioRol) {
                return $query->leftJoin("usuario_asignaturas", "usuario_asignaturas.idCurso", "=", "cursos.id")
                    ->where('usuario_asignaturas.idEstabUsuarioRol', $idEstabUsuarioRol);
            });
        }
        $cursos = $cursos->where('cursos.estado', 'Activo')
            ->where('cursos.idPeriodo', $idPeriodo)
            ->where('cursos.id', $idCurso)
            ->distinct()
            ->get();

        // 🔹 2️⃣ Obtener los ID de grados
        $idGrados = $cursos->pluck('idGrado')->unique()->filter();

        // 🔹 3️⃣ Obtener asignaturas desde master agrupadas por idGrado
        $asignaturas = Asignatura::whereIn('idGrado', $idGrados)
            ->get()
            ->groupBy('idGrado');

        // 🔹 4️⃣ Mapear datos correctamente
        $cursos->transform(function ($curso) use ($asignaturas) {
            return [
                'idCurso' => $curso->id,  // ✅ Corregido: `idCurso` no existe en `Curso`, se usa `id`
                'idGrado' => $curso->idGrado,
                'asignaturas' => $asignaturas[$curso->idGrado] ?? collect(), // ✅ Devuelve colección vacía si no hay asignaturas
            ];
        });

        // 🔹 5️⃣ Formatear la respuesta final
        return $cursos->flatMap(function ($curso) {
            return $curso['asignaturas']->map(function ($asignatura) {
                return [
                    'id' => $asignatura->id,
                    'nombre' => $asignatura->nombre,
                    'idGrado' => $asignatura->idGrado,
                ];
            });
        })->values();
    }

    /**
     * Obtiene las asignaturas del curso
     * @return \Illuminate\Http\Response
     */
    public function getAsignaturasCurso(Request $request, $idCurso, $idPeriodoHistorico)
    {
        $user = $request->user()->getUserData();

        // 1️⃣ OBTENER ID DEL PERIODO
        $idPeriodo = ($idPeriodoHistorico === 'null' || $idPeriodoHistorico === 'undefined')
        ? $user['periodo']['id']
            : $idPeriodoHistorico;

        // 2️⃣ OBTENER EL CURSO EN LA CONEXIÓN 'establecimiento'
        $curso = Curso::on('establecimiento')
        ->where('id', $idCurso)
            ->where('estado', 'Activo')
            ->where('idPeriodo', $idPeriodo)
            ->firstOrFail();

        // 3️⃣ OBTENER ASIGNATURAS EN LA CONEXIÓN 'master' QUE COINCIDAN CON EL GRADO DEL CURSO
        $asignaturas = Asignatura::on('master')
        ->where('idGrado', $curso->idGrado)
            ->where('estado', 'Activo')
            ->orderBy('id')
            ->get();

        return response()->json($asignaturas);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function getActivos()
    // {
    //     $asignaturas = UsuarioAsignatura::getAsignaturaCursoActiva();

    //     return $asignaturas;
    // }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getAsignaturasGrado($idgrado)
    {
        return Asignatura::getAsignaturasGrado($idgrado);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function getDocentesAsignaturas($idPeriodo)
    // {
    //     $asignaturas = Asignatura::select(
    //         'asignaturas.id',
    //         'asignaturas.nombre as nombreAsignatura',
    //         'cursos.id as idCurso',
    //         'users.rut as rutDocente',
    //         'users.nombres',
    //         'users.primerApellido',
    //         'users.segundoApellido',
    //         'users.email'
    //     )
    //         ->leftJoin("cursos", "cursos.idGrado", "=", "asignaturas.idGrado")
    //         ->leftJoin("usuario_asignaturas", "asignaturas.id", "=", "usuario_asignaturas.idAsignatura")
    //         ->leftJoin("usuario_establecimientos", "usuario_asignaturas.idUsuarioEstablecimiento", "=", "usuario_establecimientos.id")
    //         ->leftJoin("users", "users.id", "=", "usuario_establecimientos.idUsuario")
    //         ->where('cursos.idPeriodo', $idPeriodo)
    //         ->where('asignaturas.estado', 'Activo')
    //         ->where('cursos.estado', 'Activo')
    //         ->where('cursos.estado', 'Activo')
    //         ->distinct('asignaturas.id')
    //         ->get();

    //     return $asignaturas;
    // }

}
