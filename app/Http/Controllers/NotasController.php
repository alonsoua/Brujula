<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PuntajeIndicador;
use App\Models\Establecimiento;
use App\Models\NotasConversion;
use App\Models\Alumno;
use App\Models\Notas;
use Illuminate\Support\Facades\DB;

class NotasController extends Controller
{

    public function getNotasAsignatura($idPeriodo, $idCurso, $idAsignatura) {
        return Notas::getNotasAsignatura(
            $idPeriodo,
            $idCurso,
            $idAsignatura,
        );
    }

    public function getAllNotasCurso($idPeriodo, $idCurso) {
        return Notas::getAllNotasCurso(
            $idPeriodo,
            $idCurso,
        );
    }

    public function calcularNota(Request $request, $idAlumno, $idCurso, $idAsignatura, $idPeriodo, $idObjetivo)
    {
        $user = $request->user();
        $establecimiento = Establecimiento::getAll($user->idEstablecimientoActivo);
        $puntajesNormal = PuntajeIndicador::getPuntajesAlumno(
            $idPeriodo,
            $idAlumno,
            $idAsignatura,
            $idObjetivo
        );
        $puntajesPersonalizados = PuntajeIndicador::getPuntajesAlumnoPersonalizado(
            $idPeriodo,
            $idAlumno,
            $idAsignatura,
            $idObjetivo
        );

        $puntajes = array();
        foreach ($puntajesNormal as $pn => $puntaje) {
            array_push($puntajes, $puntaje);
        }

        foreach ($puntajesPersonalizados as $pp => $puntajePersonalizado) {
            array_push($puntajes, $puntajePersonalizado);
        }

        $notas = Notas::getNotaObjetivo($idAlumno, $idCurso, $idPeriodo, $idAsignatura, $idObjetivo);
        $cantidadPuntajes = count($puntajes);
        if ($cantidadPuntajes > 0) {
            $puntajeObtenido = 0;
            foreach ($puntajes as $key => $puntaje) {
                $puntajeObtenido += $puntaje->puntaje;
            }

            $cantidadIndicadores = count($puntajes);
            if ($puntajeObtenido > 0) {
                $notaConversion = NotasConversion::getNotasConversion($cantidadIndicadores, $puntajeObtenido, $establecimiento[0]->idPeriodoActivo, $user->idEstablecimientoActivo);
                $notaConvertida = $notaConversion[0]->nota;
            } else {
                $notaConvertida = '2.0';
            }

            if (count($notas) > 0) {
                $data = array(
                    'nota' => $notaConvertida,
                    'idNota' => $notas[0]->id,
                );
                $update = $this->update($data);
                return $update;
            } else {
                $data = array(
                    'nota' => $notaConvertida,
                    'idAlumno' => $idAlumno,
                    'idCurso' => $idCurso,
                    'idAsignatura' => $idAsignatura,
                    'idPeriodo' => $idPeriodo,
                    'idObjetivo' => $idObjetivo,
                );
                $store = $this->store($data);
                return $store;
            }


        } else {
            // si existe nota, la elimina
            // si no existe nota no hace nada
            if (count($notas) > 0) {
                $destroy = $this->destroy($notas[0]->id);
                return $destroy;
            }
        }

    }

    public function calcularNotaCurso($idCurso, $idAsignatura, $idPeriodo, $idObjetivo)
    {
        // consultar alumnos Activos del curso
        $alumnos = Alumno::getAlumnosCurso($idCurso);
        foreach ($alumnos as $key => $alumno) {
            $this->calcularNota($alumno->id, $idCurso, $idAsignatura, $idPeriodo, $idObjetivo);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store($data)
    {
        try {
            DB::transaction(function () use ($data) {
                Notas::Create([
                    'nota'        => floatval($data['nota']),
                    'idAlumno'    => $data['idAlumno'],
                    'idCurso'     => $data['idCurso'],
                    'idAsignatura'=> $data['idAsignatura'],
                    'idPeriodo'   => $data['idPeriodo'],
                    'idObjetivo'  => $data['idObjetivo'],
                ]);

                return response(null, 200);
            });

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
    public function update($data)
    {
        try {
            $nota = Notas::findOrFail($data['idNota']);

            $nota->nota = $data['nota'];
            $nota->save();

            return response('success', 200);
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
    public function destroy($id)
    {
        try {
            $nota = Notas::findOrFail($id);
            $nota->delete();
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }
}
