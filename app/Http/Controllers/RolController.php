<?php

namespace App\Http\Controllers;

use App\Models\Master\Rol;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RolController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Rol::where('tipo', 'Interno')->get();
    }
}
