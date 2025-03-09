<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class MigrateTenantsRollback extends Command
{
    protected $signature = 'tenants:rollback {--step=1}';
    protected $description = 'Ejecuta el rollback de migraciones en todas las bases de datos de los establecimientos';

    public function handle()
    {
        // Obtener todos los establecimientos activos desde la base de datos principal (master)
        $establecimientos = DB::connection('master')->table('establecimientos')->where('estado', true)->get();

        foreach ($establecimientos as $establecimiento) {
            try {
                logger()->info("Procesando rollback para establecimiento: {$establecimiento->bd_name}", [
                    'host' => $establecimiento->bd_host,
                    'port' => $establecimiento->bd_port,
                    'database' => $establecimiento->bd_name,
                    'username' => $establecimiento->bd_user,
                ]);

                // Desencripta la contraseña
                $password = decrypt($establecimiento->bd_pass);

                // Configurar la conexión para este establecimiento
                Config::set('database.connections.establecimiento', [
                    'driver' => 'mysql',
                    'host' => $establecimiento->bd_host ?? env('DB_HOST', '127.0.0.1'),
                    'port' => $establecimiento->bd_port ?? env('DB_PORT', '3306'),
                    'database' => $establecimiento->bd_name,
                    'username' => $establecimiento->bd_user,
                    'password' => $password,
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'strict' => true,
                    'engine' => null,
                ]);

                Artisan::call('config:clear');

                DB::purge('establecimiento');
                DB::reconnect('establecimiento');
                DB::setDefaultConnection('establecimiento');

                logger()->info("Conexión establecida con éxito para {$establecimiento->bd_name}");

                $this->info("Ejecutando rollback para {$establecimiento->bd_name}...");

                // Ejecutar rollback con el número de pasos especificado
                Artisan::call('migrate:rollback', [
                    '--database' => 'establecimiento',
                    '--path' => 'database/migrations/establecimiento',
                    '--step' => $this->option('step'), // Permite definir cuántos pasos de rollback ejecutar
                    '--force' => true, // Evita confirmación manual
                ]);

                $this->info("Rollback completado para {$establecimiento->bd_name}.");
            } catch (\Exception $e) {
                logger()->error("Error en rollback para {$establecimiento->bd_name}: " . $e->getMessage());
            }
        }

        $this->info("Todas las operaciones de rollback han sido ejecutadas.");
    }
}
