<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiagnosticoPie extends Model
{
    use HasFactory;
    protected $connection = 'establecimiento';
    protected $table = "diagnosticos_pie";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'tipoNee',
    ];
}
