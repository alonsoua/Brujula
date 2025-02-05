<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Curso;
use App\Models\Alumno;
use App\Models\Master\Establecimiento as MasterEstablecimiento;
use App\Models\Master\Grado;
use App\Models\UsuarioAsignatura;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user()->getUserData();
        return Curso::getAll($user['periodo']['id']);
    }

    /**
     * Obtiene los cursos asignados al usuario
     * * $idUsuarioEstablecimiento
     * @return \Illuminate\Http\Response
     */
    public function getCursosUsuario(Request $request, $idPeriodoHistorico)
    {
        $user = $request->user()->getUserData();
        $idEstabUsuarioRol = $user['rolActivo']['idEstabUsuarioRol'];
        $idPeriodo = $idPeriodoHistorico === 'null' || 'undefined' ? $user['periodo']['id'] : $idPeriodoHistorico;

        $cursos = Curso::select(
            'cursos.id',
            'cursos.nombre',
            'cursos.letra',
            'cursos.idProfesorJefe',
            'cursos.idGrado'
        )
            ->when(!is_null($idEstabUsuarioRol), function ($query) use ($idEstabUsuarioRol) {
                return $query->leftJoin("usuario_asignaturas", "usuario_asignaturas.idCurso", "=", "cursos.id")
                ->where('usuario_asignaturas.idEstabUsuarioRol', $idEstabUsuarioRol);
            })
            ->where('cursos.estado', 'Activo')
            ->where('cursos.idPeriodo', $idPeriodo)
            ->orderBy('cursos.idGrado')
            ->orderBy('cursos.letra')
            ->distinct()
            ->get();

        return $cursos;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function getCursoEstablecimientoActivo(Request $request, $idEstablecimiento, $idPeriodo)
    // {
    //     return UsuarioAsignatura::select(
    //         'cursos.id',
    //         'cursos.letra',
    //         'cursos.idProfesorJefe',
    //         'cursos.idGrado',
    //         'grados.nombre as nombreGrado',
    //         'grados.idNivel'
    //     )
    //         ->leftJoin("cursos", "cursos.id", "=", "usuario_asignaturas.idCurso")
    //         ->leftJoin("grados", "cursos.idGrado", "=", "grados.id")
    //         ->where('cursos.idEstablecimiento', $idEstablecimiento)
    //         ->where('cursos.idPeriodo', $idPeriodo)
    //         ->where('cursos.estado', 'Activo')
    //         ->orderBy('cursos.idGrado')
    //         ->orderBy('cursos.letra')
    //         ->distinct()
    //         ->get();
    // }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getActivos(Request $request)
    {
        $user = $request->user()->getUserData();
        return Curso::getAllEstado('Activo', $user['periodo']['id']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function getActivosEstablecimiento(Request $request, $idEstablecimiento)
    // {
    //     $user = $request->user();
    //     $idPeriodo = $user->idPeriodoActivo;
    //     if ($idPeriodo === null) {
    //         $establecimiento = MasterEstablecimiento::getAll($user->idEstablecimientoActivo);
    //         $idPeriodo = $establecimiento[0]['idPeriodoActivo'];
    //     }

    //     return Curso::getAll($idEstablecimiento, $idPeriodo);
    // }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCursoImportCSV($idGrado, $letra, $idPeriodo)
    {
        // Buscar el curso en la base de datos
        $curso = Curso::select('id')
            ->where('letra', '=', $letra)
            ->where('idGrado', '=', $idGrado)
            ->where('idPeriodo', '=', $idPeriodo)
            ->where('estado', 'Activo')
            ->first();

        // Si el curso existe, devolver su ID
        return $curso ? $curso->id : $this->storeImportCSV($idGrado, $letra, $idPeriodo);
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
            'cantidad'          => 'required',
            'idGrado'           => 'required',
        ]);

        try {
            
            DB::transaction(function () use ($request) {

                $user = $request->user()->getUserData();
                $cantidad = intval($request->input('cantidad'));
                $grado = Grado::find($request->input('idGrado'));
                if (!$grado) {
                    throw new \Exception("Grado no encontrado");
                }
                $letra = 'A';
                for ($i = 0; $i < $cantidad; $i++) {
                    Curso::Create(
                        [
                            'nombre'         => $grado->nombre,
                            'letra'          => $letra,
                            'idProfesorJefe' => Null,
                            'idGrado'        => $request->input('idGrado'),
                            'idPeriodo'      => $user['periodo']['id'],
                            'estado'         => 'Activo',
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    private function storeImportCSV($idGrado, $letra, $idPeriodo)
    {
        try {
            return DB::transaction(function () use ($idGrado, $letra, $idPeriodo) {
                // Obtener el nombre del grado
                $grado = Grado::find($idGrado);
                if (!$grado) {
                    throw new \Exception("Grado no encontrado");
                }

                // Crear el nuevo curso
                $curso = Curso::create([
                        'nombre'         => $grado->nombre, // Nombre basado en el grado
                        'letra'          => $letra,
                        'idProfesorJefe' => null,
                        'idGrado'        => $idGrado,
                        'idPeriodo'      => $idPeriodo,
                        'estado'         => 'Activo',
                ]);

                return $curso->id; // Retorna el ID del nuevo curso
            });
        } catch (\Throwable $th) {
            logger()->error("Error al crear curso: " . $th->getMessage());
            return null; // Retorna null en caso de error
        }
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
            'letra' => 'required',
        ]);

        try {
            $curso = Curso::findOrFail($id);

            $letra    = $request->input('letra');

            $curso->letra   = $letra;
            $curso->save();

            return response(null, 200);
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function ordenarLista(Request $request, $idCurso)
    // {
    //     try {

    //         foreach ($request->input('lista') as $key => $lista_alumno) {
    //             $alumno = Alumno::findOrFail($lista_alumno['id']);
    //             $alumno->numLista = $lista_alumno['orden'];
    //             $alumno->save();
    //         }

    //         return response('success', 200);
    //     } catch (\Throwable $th) {
    //         return response($th, 500);
    //     }
    // }
}
