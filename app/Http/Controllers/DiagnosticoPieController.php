<?php

namespace App\Http\Controllers;

use App\Models\DiagnosticoPie;
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
