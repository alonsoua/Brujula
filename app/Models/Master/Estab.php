<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estab extends Model
{
    use HasFactory;

    protected $table = 'estab';
    protected $connection = 'master';

    public $timestamps = false;

    protected $fillable = [
        'bd_name',
        'bd_pass',
        'bd_user',
        'bd_host',
        'bd_port',
        'nombre',
        'rbd',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}