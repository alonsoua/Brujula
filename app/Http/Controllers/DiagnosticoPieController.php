<?php

namespace App\Http\Controllers;

use App\Models\Master\DiagnosticoPie;
use Illuminate\Http\Request;

class DiagnosticoPieController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return DiagnosticoPie::all();
    }

}
