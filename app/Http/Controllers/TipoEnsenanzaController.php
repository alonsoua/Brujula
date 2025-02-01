<?php

namespace App\Http\Controllers;

use App\Models\Master\TipoEnsenanza;
use Illuminate\Http\Request;

class TipoEnsenanzaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return TipoEnsenanza::getAll();
    }

}
