<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estab extends Model
{
    /**
     * Tabla de bd y bd
     */
    protected $table = 'estabs';
    protected $connection = 'master';

    public $timestamps = false;

    use HasFactory;
}
