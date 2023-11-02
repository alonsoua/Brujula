<?php

namespace App\Http\Controllers;

use App\Models\dash_ld_conexion;
use App\Models\dash_ld_conexion_log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getLdConexions(Request $request, $idPeriodo)
    {
        $user = $request->user();
        $idEstablecimiento = $user->idEstablecimientoActivo;
        $dash_ld_conexion = dash_ld_conexion::select('*')
            ->where('idEstablecimiento', $idEstablecimiento)
            ->where('idPeriodo', $idPeriodo)
            ->get();
        foreach ($dash_ld_conexion as $key => $ld) {
            $dash_ld_conexion_log = dash_ld_conexion_log::select('*')
                ->where('idLdConexion', $ld['id'])
                ->get();
            $ld['logs'] = $dash_ld_conexion_log;
        }
        return $dash_ld_conexion;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addLdConexion(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $user = $request->user();
                $idEstablecimiento = $user->idEstablecimientoActivo;
                $idPeriodo = $request['idPeriodo'];
                $idUsuario = $user->id;
                $dash_ld_conexion = dash_ld_conexion::Create([
                    'idEstablecimiento' => $idEstablecimiento,
                    'idPeriodo'         => $idPeriodo,
                    'idUsuario'         => $idUsuario,
                ]);

                foreach ($request['logs'] as $key => $log) {
                    dash_ld_conexion_log::Create([
                        'state'         => $log['state'],
                        'message'       => $log['message'],
                        'nombreCurso'   => $log['nombreCurso'],
                        'idLdConexion' => $dash_ld_conexion->id,
                    ]);
                }
            });
            return response()->json(['status' => 'success', 'message' => 'Logs guardados con éxito!.']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th]);
        }
    }
}
