<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class MigrateTenants extends Command
{
    protected $signature = 'tenants:migrate';
    protected $description = 'Ejecuta las migraciones en todas las bases de datos de los establecimientos';

    public function handle()
    {
        // Obtener todos los establecimientos desde la base de datos principal (master)
        $establecimientos = DB::connection('master')->table('establecimientos')->where('estado', true)->get();

        foreach ($establecimientos as $establecimiento) {
            try {
                logger()->info("Procesando establecimiento: {$establecimiento->bd_name}", [
                    'host' => $establecimiento->bd_host,
                    'port' => $establecimiento->bd_port,
                    'database' => $establecimiento->bd_name,
                    'username' => $establecimiento->bd_user,
                    'password_encrypted' => $establecimiento->bd_pass,
                ]);

                // Desencripta la contraseña
                $password = decrypt($establecimiento->bd_pass);

                // Imprime la contraseña desencriptada en los logs
                logger()->info('Contraseña desencriptada:', ['password' => $password]);

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

                $this->info("Ejecutando migraciones para {$establecimiento->bd_name}...");

                // Ejecutar migraciones en esta conexión
                Artisan::call('migrate', [
                    '--database' => 'establecimiento',
                    '--path' => 'database/migrations/establecimiento',
                    '--force' => true, // Para asegurarse de que se ejecuta sin confirmación
                ]);

                $this->info("Migraciones completadas para {$establecimiento->bd_name}.");
            } catch (\Exception $e) {
                logger()->error("Error en el establecimiento {$establecimiento->bd_name}: " . $e->getMessage());
            }
        }

        $this->info("Todas las migraciones de los establecimientos han sido ejecutadas.");
    }
}
