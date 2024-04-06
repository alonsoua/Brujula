<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait InformeHogarPDFTrait
{


  public function formatCorrelativo($correlativo)
  {
    $largoCorrelativo = strlen($correlativo);

    if ($largoCorrelativo <= 4) {
      $largo = 4 - $largoCorrelativo;
      for ($i = 0; $i < $largo; $i++) {
        $correlativo = '0' . $correlativo;
      }
    }

    return $correlativo;
  }

  public function formatoPesos($numero)
  {
    $numero = '$' . number_format($numero, 0, '', '.');
    return $numero;
  }

  public function formatoMiles($numero)
  {
    $numero = number_format($numero, 0, '', '.');
    return $numero;
  }

  /**
   * Hoja de estilo
   *
   */
  public function style()
  {
    return "
          body {
            font-size: 13px;
          }

          @font-face {
            font-family: 'source_sans_proregular';
            src: local('Source Sans Pro'), url('fonts/sourcesans/sourcesanspro-regular-webfont.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;

        }

          header {
            left: 0px;
            right: 0px;
            position:fixed;
            top:0;
          }

          footer {
            left: -50px;
            right: 0px;
            position:fixed;
            bottom: -30;
          }

          .header-l {
            margin-right: 0px;
          }

          .header-c {
            width: 325px;
          }

          .header-r {
            padding-left: 5px;
            padding-right: 5px;
            text-align: center;
          }

          .txt-sm {
            font-size: 11px;
            font-weight: 300;
            line-height: 5px;
          }

          .txt-msje {
            font-size: 14px;
          }

          .titulo {
          }

          .nivel-logro {
            align: center;
            margin-top: 50px;
            margin-bottom: 30px;
          }
          .datos-alumno {
            border-top: 0px;
            border-right: 0px;
            border-left: 0px;
          }

          .border-celeste {
            border: 1px solid #2ab0f0;
          }

          .main {
            margin-top: 80px;
          }
          .div-footer {
            margin-top: 40px;
          }
          .div-footerRetirado {
            margin-top: 10px;
          }



          .tabla-notas {
            border-collapse: collapse;
            border: 1px solid black;
            width: 100% !important;
            margin-left: -30px !important;
          }

          .firmas {
            margin-top: 40px;
          }

          table {
            border: 1px solid black;
            width: 100%;
            border-collapse: collapse;
          }

          th, td {
            border-collapse: collapse;
            border: 1px solid black;
            height: 25px;
            padding: 1px;
          }

          .td-notas {
            min-width: 6px;
          }


          .td-promedio {
            min-width: 15px;
          }




          .sin-borde {
              border: 0 !important;
          }

          .no-b-top {
            border-top: 0 !important;
          }
          .no-b-bottom {
            border-bottom: 0 !important;
          }
          .no-b-left {
            border-left: 0 !important;
          }
          .no-b-right {
            border-right: 0 !important;
          }

          .sin-padding {
              padding: -3px! important;
          }
          .text-center {
              text-align: center;
          }
          .text-left {
              text-align: left;
          }
          .text-right {
              text-align: right;
          }
          .text-top {
            text-align: start;
          }
          .text-justify {
            text-align: justify;
          }

          .tdLogo {
              width: 180px;
              margin-right: 0px;
          }
          .tdInfo {
              width: 325px;
          }

      ";
  }

  /**
   * ENCABEZADO
   *
   * @param  int  $correlativo
   * @param  String  $fecha
   */
  public function encabezado($establecimiento)
  {

    $insignia =  base_path() . '/storage/app/public/insignias_establecimientos/' . $establecimiento['insignia'];

    return '
        <table class="sin-borde">
          <tbody>
            <tr>
                <td class="sin-borde header-l">
                    <img src="' . $insignia . '" alt="Insignia" width="100"/>
                </td>

                <td class="sin-borde header-c">
                </td>

                <td class="sin-borde header-r txt-sm">
                    <p><i>' . $establecimiento['nombre'] . '</i></p>
                    <p><i>Quintero</i></p>
                    <p><i>"Donde tod@s podemos aprender"</i></p>
                </td>
            </tr>
          </tbody>
        </table>
      ';
  }


  /**
   * ALUMNOS
   *
   */
  public function datosAlumno($alumno, $curso)
  {

    $nombreCompleto    = $alumno['nombres'] . ' ' . $alumno['primerApellido'] . ' ' . $alumno['segundoApellido'];
    $curso       = $curso['nombre'] . ' ' . $curso['letra'];
    $semestre = '1er Semestre';
    $nombreDocente = '';

    $fechaActual = date('d-m-Y');
    // $fechaActual = !is_null($alumno) ? $alumno->telefono : '';

    return '
        <table class="sin-borde">
          <tbody>
              <tr>
                  <td class="border-celeste no-b-top no-b-left no-b-right" colspan="2"><b>Nombre Estudiante:</b> ' . $nombreCompleto . '</td>
              </tr>
              <tr>
                  <td class="border-celeste no-b-left"><b>Curso:</b> ' . $curso . '</td>
                  <td class="border-celeste no-b-bottom no-b-right"><b>Fecha:</b> ' . $fechaActual . '</td>
              </tr>
              <tr>
                  <td class="border-celeste no-b-bottom no-b-left"><b>Nombre Docente:</b> ' . $nombreDocente . '</td>
                  <td class="border-celeste no-b-bottom no-b-right"><b></b></td>
              </tr>
          </tbody>
      </table>

      ';
  }

  /**
   * HTML TABLA NOTAS
   *
   * @param  Array  $asignaturas
   * @param  Array  $notas
   */
  public function notas($asignaturas, $notas)
  {

    // Contamos la cantidad de notas por asignatura,
    // Para saber cuantas columnas van sin notas.
    $columnasNotas = 0;
    foreach ($asignaturas as $key => $asignatura) {

      if ($asignatura['nombreAsignatura'] !== 'Orientación') {
        $numNotas = 0;
        foreach ($notas as $key2 => $nota) {
          $numNotas = $asignatura['id'] === $nota['idAsignatura']
            ? $numNotas + 1
            : $numNotas;
        }
        $columnasNotas = $numNotas > $columnasNotas
          ? $numNotas
          : $columnasNotas;
      }
    }

    $htmlHeadNotas = '
            <tr>
                <th class="text-center">ASIGNATURA</th>
                <th class="text-center" colspan="' . $columnasNotas . '">NIVEL DE LOGRO</th>
                <th class="colDescripcion" colspan="1">PROMEDIO</th>
            </tr>
        ';


    $htmlBodyNotas = '';
    $promedioFinal = 0;
    $promedioFinalCount = 0;
    foreach ($asignaturas as $key => $asignatura) {

      if ($asignatura['nombreAsignatura'] !== 'Orientación') {

        $htmlBodyNotas .= '
              <tr>
                <td class="text-center" style="margin: 200px! important;">
                  ' . $asignatura['nombreAsignatura'] . '
                </td>
            ';

        // Columnas con NOTAS
        $numNotas = 0;
        $sumaNotas = 0;
        $divisorNotas = 0;
        foreach ($notas as $key2 => $nota) {
          if ($asignatura['id'] === $nota['idAsignatura']) {
            $numNotas = $numNotas + 1;
            $sumaNotas = $sumaNotas + $nota['nota'];
            $divisorNotas = $divisorNotas + 1;
            $nivelLogro = $this->conversionNota($nota['nota']);
            $htmlBodyNotas .= '
                    <td class="text-center td-notas" style="margin: 100px! important;">
                        ' . $nivelLogro . '
                    </td>
                ';
          }
        }

        // Columnas sin NOTAS
        if ($columnasNotas > $numNotas) {
          $columnasVacias = $columnasNotas - $numNotas;

          for ($i = 0; $i < $columnasVacias; $i++) {
            $htmlBodyNotas .= '
                    <td class="text-center td-notas" style="margin: 100px! important;">
                        -
                    </td>
                ';
          }
        } else if ($columnasNotas == 0 && $numNotas == 0) {
          $htmlBodyNotas .= '
                    <td class="text-center td-notas" style="margin: 100px! important;">
                        -
                    </td>
                ';
        }

        // Promedios y Promedio Final
        if ($divisorNotas != 0) {
          $promedio = $sumaNotas / $divisorNotas;
          $promedio = number_format($promedio, 1, '.', '');

          $promedioFinal += intval(str_replace('.', '', $promedio));
          $promedioFinalCount += 1;

          $promedioNivelLogro = $this->conversionNota($promedio);
        } else {
          $promedio = '-';
          $promedioNivelLogro = '-';
        }

        $htmlBodyNotas .= '
                <td class="text-center td-promedio" style="margin: 300px! important;">
                  ' . $promedioNivelLogro . '
                </td>
            ';
        // <td class="text-center td-promedio" style="margin: 300px! important;">
        //   '. $promedio .'
        // </td>

        $htmlBodyNotas .= '</tr>';
      }
    }
    if ($promedioFinal !== 0 && $promedioFinalCount !== 0) {
      $promedioFinal = $promedioFinal / $promedioFinalCount;
      $promedioFinal = str_split($promedioFinal, 1);
      $promedioFinal = $promedioFinal[0] . '.' . $promedioFinal[1];
      $promedioFinalNivelLogro = $this->conversionNota($promedioFinal);
    } else {
      $promedioFinal = '-';
      $promedioFinalNivelLogro = '-';
      $columnasNotas = 1;
    }

    $htmlBodyNotas .= '<tr>
            <td class="text-right mr-1" colspan="' . $columnasNotas + 1 . '"><b>PROMEDIO GENERAL &nbsp;</b></td>
            <td class="text-center td-promedio" style="margin: 300px! important;">' . $promedioFinalNivelLogro . '</td>
          </tr>';

    // <td class="text-center td-promedio" style="margin: 300px! important;">'.$promedioFinal.'</td>

    return '
          <table class="table-notas">
              <thead>
                  ' . $htmlHeadNotas . '
              </thead>
              <tbody>
                  ' . $htmlBodyNotas . '
              </tbody>
          </table>';
  }

  /**
   * HTML TABLA NOTAS
   *
   * @param  Array  $asignaturas
   * @param  Array  $notas
   */
  public function notasAlumnoRetirado($asignaturas, $notas)
  {

    // Contamos la cantidad de notas por asignatura,
    // Para saber cuantas columnas van sin notas.
    $columnasNotas = 0;
    foreach ($asignaturas as $key => $asignatura) {

      if ($asignatura['nombreAsignatura'] !== 'Orientación') {
        $numNotas = 0;
        foreach ($notas as $key2 => $nota) {
          $numNotas = $asignatura['id'] === $nota['idAsignatura']
            ? $numNotas + 1
            : $numNotas;
        }
        $columnasNotas = $numNotas > $columnasNotas
          ? $numNotas
          : $columnasNotas;
      }
    }

    $htmlHeadNotas = '
          <tr>
              <th class="text-center">ASIGNATURA</th>
              <th class="text-center" colspan="' . $columnasNotas . '">Notas</th>
              <th class="colDescripcion" colspan="1">PROMEDIO</th>
          </tr>
      ';


    $htmlBodyNotas = '';
    $promedioFinal = 0;
    $promedioFinalCount = 0;
    foreach ($asignaturas as $key => $asignatura) {

      if ($asignatura['nombreAsignatura'] !== 'Orientación') {

        $htmlBodyNotas .= '
            <tr>
              <td class="text-center" style="margin: 200px! important;">
                ' . $asignatura['nombreAsignatura'] . '
              </td>
          ';

        // Columnas con NOTAS
        $numNotas = 0;
        $sumaNotas = 0;
        $divisorNotas = 0;
        foreach ($notas as $key2 => $nota) {
          if ($asignatura['id'] === $nota['idAsignatura']) {
            $numNotas = $numNotas + 1;
            $sumaNotas = $sumaNotas + $nota['nota'];
            $divisorNotas = $divisorNotas + 1;
            // $nivelLogro = $this->conversionNota($nota['nota']);
            $htmlBodyNotas .= '
                  <td class="text-center td-notas" style="margin: 100px! important;">
                      ' . $nota['nota'] . '
                  </td>
              ';
          }
        }

        // Columnas sin NOTAS
        if ($columnasNotas > $numNotas) {
          $columnasVacias = $columnasNotas - $numNotas;

          for ($i = 0; $i < $columnasVacias; $i++) {
            $htmlBodyNotas .= '
                  <td class="text-center td-notas" style="margin: 100px! important;">
                      -
                  </td>
              ';
          }
        } else if ($columnasNotas == 0 && $numNotas == 0) {
          $htmlBodyNotas .= '
                  <td class="text-center td-notas" style="margin: 100px! important;">
                      -
                  </td>
              ';
        }

        // Promedios y Promedio Final
        if ($divisorNotas != 0) {
          $promedio = $sumaNotas / $divisorNotas;
          $promedio = number_format($promedio, 1, '.', '');

          $promedioFinal += intval(str_replace('.', '', $promedio));
          $promedioFinalCount += 1;

          // $promedioNivelLogro = $this->conversionNota($promedio);
        } else {
          $promedio = '-';
          // $promedioNivelLogro = '-';
        }

        $htmlBodyNotas .= '
              <td class="text-center td-promedio" style="margin: 300px! important;">
                ' . $promedio . '
              </td>
          ';
        // <td class="text-center td-promedio" style="margin: 300px! important;">
        //   '. $promedio .'
        // </td>

        $htmlBodyNotas .= '</tr>';
      }
    }

    if ($promedioFinalCount !== 0) {

      $promedioFinal = $promedioFinal / $promedioFinalCount;
      $promedioFinal = str_split($promedioFinal, 1);
      $promedioFinal = $promedioFinal[0] . '.' . $promedioFinal[1];
      $htmlBodyNotas .= '<tr>
            <td class="text-right mr-1" colspan="' . $columnasNotas + 1 . '"><b>PROMEDIO GENERAL &nbsp;</b></td>
            <td class="text-center td-promedio" style="margin: 300px! important;">' . $promedioFinal . '</td>
          </tr>';
    } else {
      $promedioFinal = '-';
      $htmlBodyNotas .= '<tr>
            <td class="text-right mr-1" colspan="' . $columnasNotas + 2 . '"><b>PROMEDIO GENERAL &nbsp;</b></td>
            <td class="text-center td-promedio" style="margin: 300px! important;">' . $promedioFinal . '</td>
          </tr>';
    }
    // $promedioFinalNivelLogro = $this->conversionNota($promedioFinal);


    // <td class="text-center td-promedio" style="margin: 300px! important;">'.$promedioFinal.'</td>

    return '
        <table class="table-notasRetirado">
            <thead>
                ' . $htmlHeadNotas . '
            </thead>
            <tbody>
                ' . $htmlBodyNotas . '
            </tbody>
        </table>';
  }

  public function conversionNota($nota)
  {

    if ($nota >= 2.0 && $nota <= 2.9) {
      $nivelLogro = 'NO';
    }

    if ($nota >= 3.0 && $nota <= 3.9) {
      $nivelLogro = 'SL';
    }

    if ($nota >= 4.0 && $nota <= 4.9) {
      $nivelLogro = 'PL';
    }

    if ($nota >= 5.0 && $nota <= 5.9) {
      $nivelLogro = 'ML';
    }

    if ($nota >= 6.0 && $nota <= 7.0) {
      $nivelLogro = 'L';
    }

    return $nivelLogro;
  }
}
