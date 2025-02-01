<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiagnosticoPie extends Model
{
    use HasFactory;
    protected $connection = 'master';
    protected $table = "diagnosticos_pie";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'tipoNee',
        'abreviatura',
    ];
}
