<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategoria extends Model
{
    use HasFactory;

    protected $connection = 'establecimiento';
    protected $table = 'subcategorias';
    protected $primaryKey = 'id';
    protected $fillable = ['nombre', 'categoria_id'];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function preguntas()
    {
        return $this->hasMany(EncuestaPregunta::class);
    }
}
