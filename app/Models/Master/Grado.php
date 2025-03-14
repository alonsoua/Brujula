<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grado extends Model
{
    use HasFactory;
    protected $connection = 'master';
    protected $table = "grados";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'idGrado',
        'nombre',
        'idNivel',
    ];

    public function tipoEnsenanza()
    {
        return $this->belongsTo(TipoEnsenanza::class, 'idNivel', 'idNivel');
    }
}
