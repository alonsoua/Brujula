<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        Config::set('database.connections.establecimiento', [
            'driver' => 'mysql',
            'host' => $estab['bd_host'] ?? '127.0.0.1',
            'port' => $estab['bd_port'] ?? '3306',
            'database' => $estab['bd_name'],
            'username' => $estab['bd_user'],
            'password' => $estab['bd_pass'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ]);

        DB::purge('establecimiento'); // Limpia la conexión actual
        DB::reconnect('establecimiento'); // Reconecta con la nueva configuración
        DB::setDefaultConnection('establecimiento'); // Establece 'establecimiento' como la conexión por defecto
    }
}
