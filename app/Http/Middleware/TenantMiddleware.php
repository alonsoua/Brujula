<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si el usuario está autenticado
        if (Auth::check()) {

            $user = Auth::user();
            $estabBD = $user->estabBD;
            if ($estabBD) {
                $this->configureTenantConnection($estabBD);
            } else {
                return response()->json(['error' => 'No se encontró un establecimiento asociado'], 403);
            }
        }

        return $next($request);
    }

    /**
     * Configura la conexión dinámica para el establecimiento.
     *
     * @param  object  $establecimiento
     * @return void
     */
    private function configureTenantConnection($estab): void
    {
        try {
            // Verifica que la información del establecimiento se esté obteniendo correctamente
            logger()->info('Configurando conexión para el establecimiento:', [
                'host' => $estab['bd_host'],
                'port' => $estab['bd_port'],
                'database' => $estab['bd_name'],
                'username' => $estab['bd_user'],
                'password_encrypted' => $estab['bd_pass'], // Contraseña encriptada
            ]);

            // Desencripta la contraseña
            $password = decrypt($estab['bd_pass']);

            // Imprime la contraseña desencriptada en los logs
            logger()->info('Contraseña desencriptada:', ['password' => $password]);

            Config::set('database.connections.establecimiento', [
                'driver' => 'mysql',
                'host' => $estab['bd_host'] ?? '127.0.0.1',
                'port' => $estab['bd_port'] ?? '3306',
                'database' => $estab['bd_name'],
                'username' => $estab['bd_user'],
                'password' => $password, // 👈 Asegurar que la contraseña es válida
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

            logger()->info('Conexión establecida:', [
                'database' => config('database.connections.establecimiento.database'),
                'username' => config('database.connections.establecimiento.username'),
                'password' => config('database.connections.establecimiento.password'),
            ]);
        } catch (\Exception $e) {
            logger()->error('Error al desencriptar la contraseña: ' . $e->getMessage());
        }
    }
}
