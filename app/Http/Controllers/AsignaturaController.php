<?php

namespace App\Http\Controllers;

use App\Models\Master\Asignatura;
use App\Models\Curso;
use App\Models\User;
use App\Models\Establecimiento;
use App\Models\Periodo;
use App\Models\Master\Objetivo;
use App\Models\UsuarioAsignatura;
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
        $idPeriodo = $idPeriodoHistorico === 'null' || $idPeriodoHistorico === 'undefined'
        ? $user['periodo']['id']
            : $idPeriodoHistorico;

        // 🔹 1️⃣ Obtener cursos desde establecimiento
        $cursos = Curso::select('cursos.id', 'cursos.idGrado')
        ->when(!is_null($idEstabUsuarioRol), function ($query) use ($idEstabUsuarioRol) {
            return $query->leftJoin("usuario_asignaturas", "usuario_asignaturas.idCurso", "=", "cursos.id")
            ->where('usuario_asignaturas.idEstabUsuarioRol', $idEstabUsuarioRol);
        })
            ->where('cursos.estado', 'Activo')
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
     * Obtiene las asignaturas asignadas al usuario
     * * $idUsuarioEstablecimiento
     * @return \Illuminate\Http\Response
     */
    // public function getAsignaturasCurso(Request $request, $idCurso, $idPeriodoHistorico)
    // {
    //     $user = $request->user();
    //     // OBTIENE ID_PERIODO
    //     $id_periodo = $idPeriodoHistorico;
    //     if ($id_periodo === 'null') {
    //         $id_periodo = $user->idPeriodoActivo === null
    //             ? Establecimiento::getIDPeriodoActivo($user->idEstablecimientoActivo)
    //             : $user->idPeriodoActivo;
    //     }

    //     $cursos = Curso::select(
    //            'asignaturas.id',
    //             'asignaturas.nombre',
    //             'asignaturas.idGrado',
    //             'cursos.id as idCurso'
    //         );

    //         $cursos = $cursos->leftJoin('asignaturas', function ($join) {
    //             $join->on('asignaturas.idGrado', '=', 'cursos.idGrado');
    //         });
    //     $cursos = $cursos->where('cursos.estado', 'Activo')
    //         ->where('asignaturas.estado', 'Activo')
    //         ->where('cursos.idPeriodo', $id_periodo)
    //         ->where('cursos.id', $idCurso)
    //         ->orderBy('asignaturas.id')
    //         ->distinct()
    //         ->get();
    //     return $cursos;
    // }


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
        return Asignatura::getAllGrado($idgrado);
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
