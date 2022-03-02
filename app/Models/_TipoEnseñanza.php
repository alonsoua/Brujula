<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoEnseñanza extends Model
{
    use HasFactory;

    protected $table = "tipo_enseñanza";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'idNivel',
        'nombre',
        'codigo',
    ];
}
