<?php

namespace App\Http\Controllers;

use App\Models\Master\Grado;
use Illuminate\Http\Request;

class GradoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $nivelesPermitidos = [10, 110];
        return Grado::whereIn('idNivel', $nivelesPermitidos)
            ->orderBy('idNivel', 'asc')
            ->orderBy('idGrado', 'asc')
            ->get();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getPorIdNivel(Request $request, $idNivel)
    {
        return Grado::where('idNivel', $idNivel)
            ->orderBy('idGrado', 'asc')
            ->get();
    }
    

}
