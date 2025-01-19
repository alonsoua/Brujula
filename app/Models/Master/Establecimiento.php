<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Establecimiento extends Model
{
    /**
     * Tabla de bd y bd
     */
    protected $table = 'establecimientos';
    protected $connection = 'master';

    public $timestamps = false;

    use HasFactory;
}
