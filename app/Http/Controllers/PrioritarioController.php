<?php

namespace App\Http\Controllers;

use App\Models\Prioritario;
use Illuminate\Http\Request;

class PrioritarioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Prioritario::all();
    }
}
