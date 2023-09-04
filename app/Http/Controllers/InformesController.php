<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use Illuminate\Http\Request;
use App\Traits\InformeHogarPDFTrait;

use PDF;

class InformesController extends Controller
{
    use InformeHogarPDFTrait;

    /**
     * PDF resource.
     * use InformeHogarPDFTrait;
     *
     * @param  int  $id
     */
    public function resumenAnualPdf(Request $request)
    {
        $tipo = $request['tipo'];
        $idCurso = $request['idCurso'];
        $fields = $request['fields'];
        $alumnosNotas = $request['alumnosNotas'];
        // return response()->json(['status' => 'success', 'message' => 'Nota Creada', 'data' => $request]);
        $curso = Curso::getCurso($idCurso);
        $nombreCurso = $curso['nombre'] . ' ' . $curso['letra'];

        $html = $this->creaResumenAnualPdf($fields, $alumnosNotas);
        // return $html;
        // $alumno = $data[0]['alumno']['numLista'] . '-' . $data[0]['alumno']['nombres'] . ' ' . $data[0]['alumno']['primerApellido'] . ' ' . $data[0]['alumno']['segundoApellido'] . ' - ' . $data[0]['curso']['nombre'];
        // print($html);
        $nombre = 'Resumen Anual - ' . $nombreCurso;

        return response()->json(['nombre' => $nombre, 'html' => $html]);
        // return $this->downloadPDF($html, $tipo, $nombre);
    }

    function downloadPDF($html, $tipo, $nombre)
    {
        ini_set('max_execution_time', 300);
        ini_set("memoria_limite", "512M");

        $pdf = PDF::loadHTML($html);
        $pdf->setPaper('A4');

        $nombrePdf = $nombre . '.pdf';

        if ($tipo == 'download') {
            return $pdf->download($nombrePdf);
        } else if ($tipo == 'read') {
            return $pdf->stream($nombrePdf);
        }
    }

    function creaResumenAnualPdf($fields, $alumnosNotas)
    {

        $style = $this->style();

        $tablaResumenAnual = '
            <table><tr>
        ';

        foreach ($fields as $keyf => $field) {
            $tablaResumenAnual .= '<th>' . $field['label'] . '</th>';
        }
        $tablaResumenAnual .= '</tr><tr>';
        foreach ($alumnosNotas as $key => $alumnoNota) {
            $tablaResumenAnual .= '
            <td>' . $alumnoNota['numLista'] . '</td>
            <td>' . $alumnoNota['nombreAlumno'] . '</td>
            <td>' . $alumnoNota['nombreDiagnostico'] . '</td>
            <td>' . $alumnoNota['nombrePrioritario'] . '</td>';

            foreach ($alumnoNota['promedioAsignaturas'] as $keyp => $promedio) {
                $tablaResumenAnual .= '<td>' . $promedio['nota'] . ' / ' . $promedio['nivel'] . '</td>';
            }
            $tablaResumenAnual .= '
            <td>' . $alumnoNota['promedioFinal'] . '</td>
            <td>' . $alumnoNota['nivelFinal'] . '</td>
            ';
        }
        $tablaResumenAnual .= '</tr></table>';

        return '
            <html>
                <header>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                    <style>
                        ' . $style . '
                    </style>
                </header>
                <body style="font-family:sans-serif;">
                    <div class="main">
                        <div class="text-center">
                            <h2>Resumen Anual</h2>
                        </div>

                        <div class="datos-alumno">
                            ' . $tablaResumenAnual . '
                        </div>


                        <div style="margin-top: 120px;">
                            ' . $tablaResumenAnual . '
                        </div>
                    </div>

                </body>
            </html>
        ';
    }
}
