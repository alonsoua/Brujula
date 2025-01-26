<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Establecimiento;
use App\Models\Master\Periodo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Config;

class EstablecimientoController extends Controller
{
    protected $url;

    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $establecimientos = Establecimiento::getAll($user->idEstablecimientoActivo);
        foreach ($establecimientos as $key => $establecimiento) {
            // agregamos código y nombre
            if ($establecimiento['insignia']) {
                $establecimiento['insignia'] = $this->url->to('/') . '' . Storage::url(
                    'insignias_establecimientos/' . $establecimiento['insignia']
                );
            }
        }

        return $establecimientos;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getActivos(Request $request)
    {
        $user = $request->user();
        $establecimientos = Establecimiento::getAllActivos($user->idEstablecimientoActivo);
        foreach ($establecimientos as $key => $establecimiento) {
            // agregamos código y nombre
            if ($establecimiento['insignia']) {
                $establecimiento['insignia'] = $this->url->to('/') . '' . Storage::url(
                    'insignias_establecimientos/' . $establecimiento['insignia']
                );
            }
        }

        return $establecimientos;
    }


    public function getrbd($rbd)
    {
        try {
            $establecimiento = Establecimiento::select(
                'establecimientos.id',
                'establecimientos.rbd',
                'establecimientos.idPeriodoActivo',
            )
                ->where('establecimientos.rbd', '=', $rbd)
                ->first();
            if ($establecimiento != null) {
                return $establecimiento;
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function validateRequest(Request $request)
    {
        $request->validate([
            'rbd' => 'required|unique:establecimientos|max:20',
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email',
            'telefono' => 'required|string|max:15',
            'direccion' => 'required|string|max:255',
            'dependencia' => 'required|string|max:50',
        ]);
    }

    public function store(Request $request)
    {
        $this->validateRequest($request);

        DB::beginTransaction();

        try {
            $establecimiento = $this->createEstablecimiento($request);
            $dbCredenciales = $this->createDatabase($establecimiento);
            $this->updateDatabaseCredenciales($establecimiento, $dbCredenciales);
            $this->createDefaultUsuarios($establecimiento->id, $request->rbd);

            $this->runMigrationsEstablecimiento($dbCredenciales);

            DB::commit();

            return response()->json([
                'message' => 'Establecimiento creado con éxito',
                'data' => $establecimiento,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al crear el establecimiento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function createEstablecimiento(Request $request): Establecimiento
    {
        try {
            $insignia = $request->input('insignia');
            $rbd = $request->input('rbd');
            if (!is_null($insignia)) {
                $nombreInsignia = formatNameImage(
                    $insignia,
                    $rbd
                );
                saveStorageImagen(
                    'insignias_establecimientos',
                    $insignia,
                    $nombreInsignia
                );
                $insignia = $nombreInsignia;
            }
            $periodos = Periodo::all();
            $lastPeriodo = $periodos->last();
            return Establecimiento::create([
                'rbd' => $request->rbd,
                'nombre' => $request->nombre,
                'insignia' => $insignia,
                'correo' => $request->correo,
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
                'dependencia' => $request->dependencia,
                'idPeriodoActivo' => $lastPeriodo['id'],
                'estado' => true,
            ]);
        } catch (\Exception $e) {
            throw new \Exception("Error al crear el stablecimiento: " . $e->getMessage());
        }
    }

    private function createDatabase(Establecimiento $establecimiento): array
    {
        $dbName = "softinno_brujula_{$establecimiento->id}";
        $dbUser = "{$establecimiento->id}_{$establecimiento->rbd}";
        $dbPass = uniqid();

        try {
            // Crear la base de datos
            DB::statement("CREATE DATABASE $dbName");

            // Crear el usuario con permisos para la base de datos
            DB::statement("CREATE USER '$dbUser'@'%' IDENTIFIED BY '$dbPass'");
            DB::statement("GRANT ALL PRIVILEGES ON $dbName.* TO '$dbUser'@'%'");
            DB::statement("FLUSH PRIVILEGES");

            return [
                'dbName' => $dbName,
                'dbUser' => $dbUser,
                'dbPass' => $dbPass,
            ];
        } catch (\Exception $e) {
            throw new \Exception("Error al crear la BD del establecimiento: " . $e->getMessage());
        }
    }

    private function updateDatabaseCredenciales(Establecimiento $establecimiento, array $dbCredenciales)
    {
        try {
            $establecimiento->update([
                'bd_name' => $dbCredenciales['dbName'],
                'bd_user' => $dbCredenciales['dbUser'],
                'bd_pass' => encrypt($dbCredenciales['dbPass']),
                'bd_host' => env('DB_HOST', '127.0.0.1'),
                'bd_port' => env('DB_PORT', '3306'),
            ]);
        } catch (\Exception $e) {
            throw new \Exception("Error al actualizar el stablecimiento: " . $e->getMessage());
        }
    }

    private function runMigrationsEstablecimiento(array $dbCredenciales)
    {
        // Configurar la conexión dinámica para la nueva base de datos
        Config::set('database.connections.establecimiento', [
            'driver' => 'mysql',
            'database' => $dbCredenciales['dbName'],
            'username' => $dbCredenciales['dbUser'],
            'password' => $dbCredenciales['dbPass'],
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ]);

        // Purgar la conexión para garantizar que se aplique la configuración actualizada
        DB::purge('establecimiento'); // Limpia la conexión actual
        DB::reconnect('establecimiento'); // Reconecta con la nueva configuración
        DB::setDefaultConnection('establecimiento');

        try {
            Artisan::call('migrate', [
                '--database' => 'establecimiento',
                '--path' => 'database/migrations/establecimiento',
                '--force' => true,
            ]);
        } catch (\Exception $e) {
            throw new \Exception("Error al ejecutar las migraciones: " . $e->getMessage());
        }
    }

    private function createDefaultUsuarios(int $idEstablecimiento, string $rbd)
    {
        $roles = [
            ['rol' => 'docente', 'idRol' => 7],
            ['rol' => 'director', 'idRol' => 3],
            ['rol' => 'utp', 'idRol' => 6],
        ];
        try {

            foreach ($roles as $role) {
                $usuarioId = DB::connection('master')->table('usuarios')->insertGetId([
                    'correo' => "{$idEstablecimiento}.{$role['rol']}@dev.cl",
                    'password' => Hash::make("{$idEstablecimiento}.123456"),
                    'rut' => '11.111.111-1',
                    'nombres' => "{$rbd}_dev",
                    'primerApellido' => ucfirst($role['rol']),
                    'segundoApellido' => 'Dev',
                    'estado' => true,
                ]);

                DB::connection('master')->table('estab_usuarios_roles')->insert([
                    'idEstablecimiento' => $idEstablecimiento,
                    'idUsuario' => $usuarioId,
                    'idRol' => $role['idRol'],
                    'estado' => true,
                ]);
            }
        } catch (\Exception $e) {
            throw new \Exception("Error al crear los usuarios: " . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Request()->validate([
            'rbd' => 'required|max:10|unique:establecimientos,rbd,' . $id . ',id',
            'nombre' => 'required|max:200',
            'correo' => 'required|email|max:80',
            'telefono' => 'required|max:25',
            'direccion' => 'required|max:250',
            'dependencia' => 'required',
            'estado' => 'required',
        ]);

        try {
            $establecimiento = Establecimiento::findOrFail($id);

            $rbd    = $request->input('rbd');
            $nombre = $request->input('nombre');

            $insignia = $request->input('insignia');
            if (!is_null($insignia)) {
                $nombreInsignia = formatNameImage(
                    $insignia,
                    $rbd
                );
                if (!is_null($nombreInsignia)) {
                    $insigniaAntigua = $establecimiento->insignia;
                    if ($insigniaAntigua) {
                        Storage::disk('insignias_establecimientos')->delete($insigniaAntigua);
                    }
                    saveStorageImagen(
                        'insignias_establecimientos',
                        $insignia,
                        $nombreInsignia
                    );
                    $establecimiento->insignia = $nombreInsignia;
                }
            } else {
                $insigniaAntigua = $establecimiento->insignia;
                if ($insigniaAntigua) {
                    Storage::disk('insignias_establecimientos')->delete($insigniaAntigua);
                }
                $establecimiento->insignia = null;
            }

            $correo      = $request->input('correo');
            $telefono    = $request->input('telefono');
            $direccion   = $request->input('direccion');
            $dependencia = $request->input('dependencia');
            $estado      = $request->input('estado');

            $establecimiento->rbd         = $rbd;
            $establecimiento->nombre      = $nombre;
            $establecimiento->correo      = $correo;
            $establecimiento->telefono    = $telefono;
            $establecimiento->direccion   = $direccion;
            $establecimiento->dependencia = $dependencia;
            $establecimiento->estado      = $estado;
            $establecimiento->save();

            return response(null, 200);
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }


    /**
     * Cambia el periodo activo del establecimiento.
     *
     * @return \Illuminate\Http\Response
     */
    public function updatePeriodoActivo(Request $request, $id)
    {
        Request()->validate([
            'idPeriodoActivo' => 'required',
        ]);

        try {
            $establecimiento = Establecimiento::findOrFail($id);

            $idPeriodoActivo          = $request->input('idPeriodoActivo');
            $fechaInicioPeriodoActivo = $request->input('fechaInicioPeriodoActivo');

            $establecimiento->idPeriodoActivo          = $idPeriodoActivo;
            $establecimiento->fechaInicioPeriodoActivo = $fechaInicioPeriodoActivo;
            $establecimiento->save();

            return response(null, 200);
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            $establecimiento = Establecimiento::findOrFail($id);
            $establecimiento->delete();
        } catch (\Throwable $th) {
            return response($th, 500);
        }
    }
}
