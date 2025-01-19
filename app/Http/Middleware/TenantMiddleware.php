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

            // Verificar si el usuario tiene un establecimiento asociado
            $establecimiento = $user->establecimientoBD;

            if ($establecimiento) {
                $this->configureTenantConnection($establecimiento);
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
    private function configureTenantConnection($establecimiento): void
    {
        Config::set('database.connections.establecimiento', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => $establecimiento->bd_name,
            'username' => $establecimiento->bd_user,
            'password' => $establecimiento->bd_pass,
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
