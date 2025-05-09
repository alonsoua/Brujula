<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SincronizacionBruLdAutomatizada extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $syncLibroController = new \App\Http\Controllers\SyncLibroController();
        $resultado = $syncLibroController->sincronizarEvaluacionesMultiples();

        $contenido = json_decode($resultado->getContent(), true);

        $this->info("Sincronización completada en {$contenido['tiempo_total']} segundos");

        if ($contenido['status'] === 'success') {
            $this->info("Estado: Exitoso");
        } else {
            $this->error("Estado: Error - {$contenido['message']}");
        }

        foreach ($contenido['resultados'] as $resultado) {
            $this->line("Usuario: {$resultado['usuario']}");
            $this->line("Estado: {$resultado['estado']}");
            $this->line("Evaluaciones procesadas: {$resultado['evaluaciones_procesadas']}");
            $this->line("----------------------------");
        }

        return 0;
    }
}
