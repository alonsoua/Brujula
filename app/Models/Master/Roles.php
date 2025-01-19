<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    use HasFactory;
    /**
     * Tabla de bd y bd
     */
    protected $table = 'roles';
    protected $connection = 'master';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id_rol';

    /**
     * The "type" of the auto-incrementing ID.
     */
    protected $keyType = 'int';

    public $timestamps = false;


    /**
     * Relación inversa con User.
     */
    public function users()
    {
        return $this->hasMany(\App\Models\Master\User::class, 'id_rol', 'id_rol');
    }
}
