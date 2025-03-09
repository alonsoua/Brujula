<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Master\Asignatura;
use App\Models\Indicador;
use App\Models\Master\Objetivo;
use App\Models\Unidad;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class JsonObjetivosController extends Controller
{
    public function store(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $json = file_get_contents($request['json']);
                $niveles = json_decode($json, true);

                foreach ($niveles as $key => $nivel) {
                    // consultar el nivel en el que ingresaremos la información
                    if ($nivel['nombre'] == 'Primero Medio') {
                        $idGrado = 19;
                    } else if ($nivel['nombre'] == 'Segundo Medio') {
                        $idGrado = 20;
                    } else if ($nivel['nombre'] == 'Tercero Medio') {
                        $idGrado = 21;
                    } else if ($nivel['nombre'] == 'Cuarto Medio') {
                        $idGrado = 22;
                    } else {
                        $idGrado = $nivel['idCodigoGrado'];
                    }

                    foreach ($nivel['Asignaturas'] as $key => $asignatura) {
                        $especial = strpos($asignatura['nombre'], 'Amanecer');
                        if (!$especial) {
                            $asignaturaCreada = Asignatura::Create([
                                'nombre'  => $asignatura['nombre'],
                                'idGrado' => $idGrado,
                                'estado'  => 'Activo',
                            ]);

                            foreach ($asignatura['Unidades'] as $key => $unidad) {

                                $unidadCreada = Unidad::Create([
                                    'nombre'  => $unidad['nombre'],
                                    'idAsignatura' => $asignaturaCreada->id,
                                    'estado'  => 'Activo',
                                ]);

                                foreach ($unidad['Objetivos'] as $key => $objetivo) {
                                    $objetivoCreado = Objetivo::Create([
                                        'nombre'  => $objetivo['nombre'],
                                        'abreviatura'  => $objetivo['abreviatura'],
                                        'priorizacion' => $objetivo['priorizacion'],
                                        'idUnidad' => $unidadCreada->id,
                                    ]);

                                    foreach ($objetivo['Indicadores'] as $key => $indicador) {
                                        Indicador::Create([
                                            'nombre'  => $indicador['nombre'],
                                            'idObjetivo' => $objetivoCreado->id,
                                            'estado'  => 'Activo',
                                        ]);
                                    }

                                    foreach ($objetivo['Actividades'] as $key => $actividad) {
                                        Actividad::Create([
                                            'nombre'      => $actividad['nombre'],
                                            'descripcion' => $actividad['descripcion'],
                                            'idObjetivo'  => $objetivoCreado->id,
                                            'estado'      => 'Activo',
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            });

            return response('success', 200);
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }
}
