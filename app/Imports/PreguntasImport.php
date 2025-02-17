<?php

namespace App\Imports;

use App\Models\EncuestaPregunta;
use App\Models\EncuestaOpcion;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class PreguntasImport implements ToCollection
{
  protected $encuesta;
  protected $opciones;

  public function __construct($encuesta, $opciones)
  {
    $this->encuesta = $encuesta;
    $this->opciones = $opciones;
  }

  public function collection(Collection $rows)
  {
    foreach ($rows as $row) {
      $pregunta = $this->encuesta->preguntas()->create([
        'orden' => $row[0],
        'pregunta' => $row[1],
        'tipo_pregunta' => 'checkbox', // Ajusta según sea necesario
      ]);

      foreach ($this->opciones as $opcionData) {
        $pregunta->opciones()->create($opcionData);
      }
    }
  }
}
