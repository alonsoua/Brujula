<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Alumno;
use App\Models\Master\Asignatura;
use App\Models\Master\Periodo;
use App\Models\Notas;
use App\Traits\InformeHogarPDFTrait;

use Barryvdh\DomPDF\Facade\Pdf;


class InformeHogarController extends Controller
{

    use InformeHogarPDFTrait;

    /**
     * PDF resource.
     * use InformeHogarPDFTrait;
     *
     * @param  int  $id
     */

    public function __construct()
    {
        $this->middleware(['auth:establecimiento'])->except(['createPDF']);
    }

    public function createPDF(Request $request, $idAlumno, $tipo, $tipoInforme)
    {

        $user = $request->user()->getUserData();
        $establecimiento = $user['establecimiento'];
        $idPeriodo = $user['periodo']['id'];

        $alumno = Alumno::getAlumno($idAlumno);
        $curso = Alumno::getAlumnoCurso($idPeriodo, $idAlumno);
        $asignaturas = Asignatura::getAsignaturasGrado($curso['idGrado']);

        $notas = Notas::getNotasAlumno($idPeriodo, $curso['id'], $idAlumno);
        $data = array();
        if ($establecimiento['rbd'] === '1855') { // ? República de francia Francia
            array_push($data, array(
                'alumno' => $alumno,
                'establecimiento' => $establecimiento,
                'curso' => $curso,
                'asignaturas' => $asignaturas,
                'notas' => $notas,
            ));

            if ($tipoInforme === 'AlumnoMatriculado') {
                $html = $this->downloadPDFEscuelaFrancia($data[0]);
            } else if ($tipoInforme === 'AlumnoRetirado') {
                $html = $this->downloadPDFEscuelaFranciaRetirado($data[0]);
            }
        }
        if ($establecimiento['rbd'] === '336699') { // ? testing
            array_push($data, array(
                'alumno' => $alumno,
                'establecimiento' => $establecimiento,
                'curso' => $curso,
                'asignaturas' => $asignaturas,
                'notas' => $notas,
            ));

            if ($tipoInforme === 'AlumnoMatriculado') {
                $html = $this->downloadPDFEscuelaFrancia($data[0]);
            } else if ($tipoInforme === 'AlumnoRetirado') {
                $html = $this->downloadPDFEscuelaFranciaRetirado($data[0]);
            }
        }

        $alumno = $data[0]['alumno']['numLista'] . '-' . $data[0]['alumno']['nombres'] . ' ' . $data[0]['alumno']['primerApellido'] . ' ' . $data[0]['alumno']['segundoApellido'] . ' - ' . $data[0]['curso']['nombre'];
        return $this->downloadPDF($html, $tipo, $alumno);
    }


    /**
     * PDF resource.
     * use InformeHogarPDFTrait;
     *
     * @param  array  $data
     */
    function downloadPDFEscuelaFrancia($data)
    {
        $style = $this->style();

        $htmlEncabezado = $this->encabezado(
            $data['establecimiento']
        );

        $htmlDatosAlumno = $this->datosAlumno(
            $data['alumno'],
            $data['curso']
        );

        $htmlTablaNotas = $this->notas(
            $data['asignaturas'],
            $data['notas']
        );

        // $cuadronivelLogro = '../storage/app/public/images/nivelLogro_' . $data['establecimiento']['rbd'] . '.png';
        $cuadronivelLogro =  base_path() . '/storage/app/public/images/nivelLogro_' . $data['establecimiento']['rbd'] . '.png';
        // $footer = '../public/storage/images/diseño_' . $data['establecimiento']['rbd'] . '.png';
        return '
        <html>
            <header>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <style>
                    ' . $style . '
                </style>
            </header>
            <body style="font-family:sans-serif;">
                <header>
                    ' . $htmlEncabezado . '
                </header>
                <div class="main">
                    <div class="text-center">
                        <h2>Informe al Hogar</h2>
                    </div>

                    <div class="datos-alumno">
                        ' . $htmlDatosAlumno . '
                    </div>

                    <div class="mensaje " style="margin-top: 40px;">
                        <h2>&nbsp;&nbsp;&nbsp; Querida Familia:</h2>
                        <p class="text-justify txt-msje">
                        Sabemos que es en la familia, el hogar, en donde se construyen los primeros y más
                        potentes aprendizajes ya que los vínculos afectivos son la base para crecer y
                        desarrollarse sana e integralmente en forma plena y armónica. Por lo mismo es
                        imprescindible que trabajemos en conjunto   <strong>Escuela y Familia</strong> para colaborarnos y
                        ampliar las oportunidades de crecimiento durante la etapa escolar de cada niño y niña.
                        Para facilitar este proceso compartimos con usted información sobre cómo el o la
                        estudiante ha ido progresando en relación a los aprendizajes, y así poder apoyar y
                        reforzar el proceso.
                        <strong>Los avances</strong> que él o la estudiante ha demostrado obtener en el logro de los
                        aprendizajes alcanzados durante los procesos de evaluación formativa se expresarán
                        de la siguiente manera:
                        </p>

                        <img src="' . $cuadronivelLogro . '" alt="Nivel de Logro" width="700" class="nivel-logro"/>

                        <p class="text-justify txt-msje">
                        </p>

                    </div>

                    <div>
                    </div>

                    <div class="mensaje">
                    </div>

                    <div style="margin-top: 120px;">
                        ' . $htmlTablaNotas . '
                    </div>
                </div>
                <div class="div-footer">
                    <p class="text-justify txt-msje">
                        OBSERVACIONES:
                        _________________________________________________________________________________________
                        _________________________________________________________________________________________
                        _________________________________________________________________________________________
                        _________________________________________________________________________________________
                        _________________________________________________________________________________________
                        _________________________________________________________________________________________
                    </p>
                </div>
                <div class="div-footer">
                    <table class="sin-borde firmas">
                        <tbody>
                            <tr>
                                <td class="sin-borde">

                                    <p class="text-center">
                                    __________________________<BR>
                                    DOCENTE GUÍA
                                    </p>
                                </td>
                                <td class="sin-borde">

                                    <p class="text-center">
                                    __________________________<BR>
                                    DIRECTOR
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>


                </body>
                </html>
            ';
    }

    /**
     * PDF resource.
     * use InformeHogarPDFTrait;
     *
     * @param  array  $data
     */
    function downloadPDFEscuelaFranciaRetirado($data)
    {

        $style = $this->style();

        $htmlEncabezado = $this->encabezado(
            $data['establecimiento']
        );

        $htmlDatosAlumno = $this->datosAlumno(
            $data['alumno'],
            $data['curso']
        );

        $htmlTablaNotas = $this->notasAlumnoRetirado(
            $data['asignaturas'],
            $data['notas']
        );

        return '
        <html>
            <header>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <style>
                    ' . $style . '
                </style>
            </header>
            <body style="font-family:sans-serif;">
                <header>
                    ' . $htmlEncabezado . '
                </header>
                <div class="main">
                    <div class="text-center">
                        <h2>Informe al Hogar</h2>
                    </div>

                    <div class="datos-alumno">
                        ' . $htmlDatosAlumno . '
                    </div>

                    <div class="mensaje " style="margin-top: 0px;">
                        <h2>&nbsp;&nbsp;&nbsp; Querida Familia:</h2>
                        <p class="text-justify txt-msje">
                        Sabemos que es en la familia, el hogar, en donde se construyen los primeros y más
                        potentes aprendizajes ya que los vínculos afectivos son la base para crecer y
                        desarrollarse sana e integralmente en forma plena y armónica. Por lo mismo es
                        imprescindible que trabajemos en conjunto   <strong>Escuela y Familia</strong> para colaborarnos y
                        ampliar las oportunidades de crecimiento durante la etapa escolar de cada niño y niña.
                        Para facilitar este proceso compartimos con usted información sobre cómo el o la
                        estudiante ha ido progresando en relación a los aprendizajes, y así poder apoyar y
                        reforzar el proceso.
                        <strong>Los avances</strong> que él o la estudiante ha demostrado obtener en el logro de los
                        aprendizajes alcanzados durante los procesos de evaluación formativa se expresarán
                        de la siguiente manera:
                        </p>

                        <p class="text-justify txt-msje">
                        </p>

                    </div>

                    <div>
                    </div>

                    <div class="mensaje">
                    </div>

                    <div style="">
                        ' . $htmlTablaNotas .
            '
                    </div>
                </div>
                <div class="div-footerRetirado">
                    <p class="text-justify txt-msje">
                        OBSERVACIONES:
                        _________________________________________________________________________________________
                        _________________________________________________________________________________________
                        _________________________________________________________________________________________
                        _________________________________________________________________________________________
                        _________________________________________________________________________________________
                        _________________________________________________________________________________________
                    </p>
                </div>
                <div class="div-footerRetirado">
                    <table class="sin-borde firmas">
                        <tbody>
                            <tr>
                                <td class="sin-borde">

                                    <p class="text-center">
                                    __________________________<BR>
                                    DOCENTE GUÍA
                                    </p>
                                </td>
                                <td class="sin-borde">

                                    <p class="text-center">
                                    __________________________<BR>
                                    DIRECTOR
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>


                </body>
            </html>
        ';
    }



    function downloadPDF($html, $tipo, $alumno)
    {
        ini_set('max_execution_time', 300);
        ini_set("memoria_limite", "512M");
        $pdf = Pdf::loadHTML($html);

        $pdf->setPaper('A4');

        $nombrePdf = $alumno . ' - informe_hogar.pdf';

        if ($tipo == 'download') {
            return $pdf->download($nombrePdf);
        } else if ($tipo == 'read') {
            return $pdf->stream($nombrePdf);
        }
    }
}
