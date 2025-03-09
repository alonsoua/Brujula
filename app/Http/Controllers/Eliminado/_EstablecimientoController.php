<?php

namespace App\Http\Controllers;

use App\Models\Establecimiento;
use App\Models\Periodo;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\UrlGenerator;

class ClienteEstablecimientoController extends Controller
{

    protected $url;

    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $establecimientos = Establecimiento::getAll($user->idEstablecimientoActivo);
        foreach ($establecimientos as $key => $establecimiento) {
            // agregamos código y nombre
            if ($establecimiento['insignia']) {
                $establecimiento['insignia'] = $this->url->to('/') . '' . Storage::url(
                    'insignias_establecimientos/' . $establecimiento['insignia']
                );
            }
        }

        return $establecimientos;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getActivos(Request $request)
    {
        $user = $request->user();
        $establecimientos = Establecimiento::getAllActivos($user->idEstablecimientoActivo);
        foreach ($establecimientos as $key => $establecimiento) {
            // agregamos código y nombre
            if ($establecimiento['insignia']) {
                $establecimiento['insignia'] = $this->url->to('/') . '' . Storage::url(
                    'insignias_establecimientos/' . $establecimiento['insignia']
                );
            }
        }

        return $establecimientos;
    }


    public function getrbd($rbd)
    {
        try {
            $establecimiento = Establecimiento::select(
                'establecimientos.id',
                'establecimientos.rbd',
                'establecimientos.idPeriodoActivo',
            )
                ->where('establecimientos.rbd', '=', $rbd)
                ->first();
            if ($establecimiento != null) {
                return $establecimiento;
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
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
            'rbd' => 'required|max:10|unique:establecimientos',
            'nombre' => 'required|max:200',
            'correo' => 'required|email|max:80',
            'telefono' => 'required|max:25',
            'direccion' => 'required|max:250',
            'dependencia' => 'required',
            'estado' => 'required',
        ]);


        try {
            DB::transaction(function () use ($request) {
                $insignia = $request->input('insignia');
                $rbd = $request->input('rbd');
                if (!is_null($insignia)) {
                    $nombreInsignia = formatNameImage(
                        $insignia,
                        $rbd
                    );
                    saveStorageImagen(
                        'insignias_establecimientos',
                        $insignia,
                        $nombreInsignia
                    );
                    $insignia = $nombreInsignia;
                }

                $periodos = Periodo::all();
                $lastPeriodo = $periodos->last();

                Establecimiento::Create([
                    'rbd'             => $rbd,
                    'nombre'          => $request->input('nombre'),
                    'insignia'        => $insignia,
                    'correo'          => $request->input('correo'),
                    'telefono'        => $request->input('telefono'),
                    'direccion'       => $request->input('direccion'),
                    'dependencia'     => $request->input('dependencia'),
                    'idPeriodoActivo' => $lastPeriodo['id'],
                    'estado'          => $request->input('estado'),
                ]);

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
        return Establecimiento::findOrFail($id);
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
            'rbd' => 'required|max:10|unique:establecimientos,rbd,' . $id . ',id',
            'nombre' => 'required|max:200',
            'correo' => 'required|email|max:80',
            'telefono' => 'required|max:25',
            'direccion' => 'required|max:250',
            'dependencia' => 'required',
            'estado' => 'required',
        ]);

        try {
            $establecimiento = Establecimiento::findOrFail($id);

            $rbd    = $request->input('rbd');
            $nombre = $request->input('nombre');

            $insignia = $request->input('insignia');
            if (!is_null($insignia)) {
                $nombreInsignia = formatNameImage(
                    $insignia,
                    $rbd
                );
                if (!is_null($nombreInsignia)) {
                    $insigniaAntigua = $establecimiento->insignia;
                    if ($insigniaAntigua) {
                        Storage::disk('insignias_establecimientos')->delete($insigniaAntigua);
                    }
                    saveStorageImagen(
                        'insignias_establecimientos',
                        $insignia,
                        $nombreInsignia
                    );
                    $establecimiento->insignia = $nombreInsignia;
                }
            } else {
                $insigniaAntigua = $establecimiento->insignia;
                if ($insigniaAntigua) {
                    Storage::disk('insignias_establecimientos')->delete($insigniaAntigua);
                }
                $establecimiento->insignia = null;
            }

            $correo      = $request->input('correo');
            $telefono    = $request->input('telefono');
            $direccion   = $request->input('direccion');
            $dependencia = $request->input('dependencia');
            $estado      = $request->input('estado');

            $establecimiento->rbd         = $rbd;
            $establecimiento->nombre      = $nombre;
            $establecimiento->correo      = $correo;
            $establecimiento->telefono    = $telefono;
            $establecimiento->direccion   = $direccion;
            $establecimiento->dependencia = $dependencia;
            $establecimiento->estado      = $estado;
            $establecimiento->save();

            return response(null, 200);
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }


    /**
     * Cambia el periodo activo del establecimiento.
     *
     * @return \Illuminate\Http\Response
     */
    public function updatePeriodoActivo(Request $request, $id)
    {
        Request()->validate([
            'idPeriodoActivo' => 'required',
        ]);

        try {
            $establecimiento = Establecimiento::findOrFail($id);

            $idPeriodoActivo          = $request->input('idPeriodoActivo');
            $fechaInicioPeriodoActivo = $request->input('fechaInicioPeriodoActivo');

            $establecimiento->idPeriodoActivo          = $idPeriodoActivo;
            $establecimiento->fechaInicioPeriodoActivo = $fechaInicioPeriodoActivo;
            $establecimiento->save();

            return response(null, 200);
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
    public function destroy(Request $request, $id)
    {
        try {
            $establecimiento = Establecimiento::findOrFail($id);
            $establecimiento->delete();
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }
}
