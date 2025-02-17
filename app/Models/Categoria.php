<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $connection = 'establecimiento';
    protected $table = 'categorias';
    protected $primaryKey = 'id';
    protected $fillable = ['nombre'];

    public function subcategorias()
    {
        return $this->hasMany(Subcategoria::class);
    }
}
