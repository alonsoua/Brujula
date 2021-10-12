<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsuarioEstablecimiento extends Model
{
    use HasFactory;

    protected $table = "usuario_establecimientos";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'idUsuario',
        'idEstablecimiento',
        'created_at',
        'updated_at',
    ];
}
