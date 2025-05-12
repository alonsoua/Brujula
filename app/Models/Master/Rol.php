<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Rol extends Model
{
    use HasFactory;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'roles';
    protected $connection = 'master';

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
        'tipo',
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
    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'id');
    }

    public static function rolHasPermisos($rolActivo, $evaluacionesActivo)
    {
        $permisos = DB::connection('master')->table('role_has_permissions')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->where('role_has_permissions.role_id', $rolActivo->idRol)
            ->select('permissions.name')
            ->get()
            ->map(function ($permiso) {
                $val = explode('_', $permiso->name);
                return [
                    'action' => $val[0] ?? null,
                    'subject' => $val[1] ?? null,
                ];
            })
            ->toArray();


        // Verificar si el usuario es profesor jefe de algún curso
        $esProfesorJefe = DB::connection('establecimiento')
            ->table('cursos')
            ->where('idProfesorJefe', $rolActivo->id)
            ->exists();

        // Si es profesor jefe, agregar permisos de sincronización LD
        if ($esProfesorJefe) {
            array_unshift($permisos, ['action' => 'read', 'subject' => 'cursos']);
            array_unshift($permisos, ['action' => 'lista', 'subject' => 'cursos']);
        }

        // Si es director agregar permisos para ver sincronización
        if ($rolActivo->idRol == 3 || $rolActivo->idRol == 6) {
            array_unshift($permisos, ['action' => 'read', 'subject' => 'sincronizacion']);
            array_unshift($permisos, ['action' => 'create', 'subject' => 'sincronizacion']);
            array_unshift($permisos, ['action' => 'update', 'subject' => 'sincronizacion']);
            array_unshift($permisos, ['action' => 'read', 'subject' => 'evaluaciones']);
            array_unshift($permisos, ['action' => 'read', 'subject' => 'informes']);

            // Elimina permisos de resumen anual
            $permisos = array_filter($permisos, function ($permiso) {
                return !($permiso['action'] == 'read' && $permiso['subject'] == 'resumenanual');
            });

            // Elimina permisos de informehogar
            $permisos = array_filter($permisos, function ($permiso) {
                return !($permiso['action'] == 'read' && $permiso['subject'] == 'informehogar');
            });
        }

        if ($evaluacionesActivo && (($rolActivo->idRol == 7 || $rolActivo->idRol == 8 || $rolActivo->idRol == 9))) {
            // Agrega permisos de evaluaciones
            array_unshift($permisos, ['action' => 'delete', 'subject' => 'evaluaciones']);
            array_unshift($permisos, ['action' => 'update', 'subject' => 'evaluaciones']);
            array_unshift($permisos, ['action' => 'create', 'subject' => 'evaluaciones']);
            array_unshift($permisos, ['action' => 'read', 'subject' => 'evaluaciones']);

            // Elimina permisos de avances
            $permisos = array_filter($permisos, function ($permiso) {
                return !($permiso['action'] == 'update' && $permiso['subject'] == 'avances');
            });

            // Elimina permisos de resumen anual
            $permisos = array_filter($permisos, function ($permiso) {
                return !($permiso['action'] == 'read' && $permiso['subject'] == 'resumenanual');
            });

            // Elimina permisos de informehogar
            $permisos = array_filter($permisos, function ($permiso) {
                return !($permiso['action'] == 'read' && $permiso['subject'] == 'informehogar');
            });
        }

        array_unshift($permisos, ['action' => 'read', 'subject' => 'home']);
        return $permisos;
    }

}
