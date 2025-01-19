<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasFactory;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * Clave primaria de la tabla.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indica si la clave primaria es auto-incremental.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Tipo de la clave primaria.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Indica si el modelo usa marcas de tiempo (timestamps).
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Los atributos que son asignables de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'guard_name',
    ];

    /**
     * Los atributos que deberían ser ocultos para la serialización.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con otros modelos, si aplica (opcional).
     */
    // public function users()
    // {
    //     return $this->hasMany(User::class, 'role_id', 'id');
    // }
}
