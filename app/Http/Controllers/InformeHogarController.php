<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Alumno;
use App\Models\Asignatura;
use App\Models\Notas;
use App\Traits\InformeHogarPDFTrait;

use PDF;


class InformeHogarController extends Controller
{

    use InformeHogarPDFTrait;

    /**
     * PDF resource.
     * use InformeHogarPDFTrait;
     *
     * @param  int  $id
     */

    public function createPDF($idPeriodo, $idAlumno, $tipo) {


        //Obtener
        /* alumnos
        /* Establecimiento
        /* Curso
        /* Asignaturas
        /* Notas
        */

        $alumno = Alumno::getAlumno($idAlumno);
        $establecimiento = Alumno::getAlumnoEstablecimiento($idAlumno);
        $curso = Alumno::getAlumnoCurso($idPeriodo, $idAlumno);
        $asignaturas = Asignatura::getAllGrado($curso[0]['idTablaGrados']);
        $notas = Notas::getNotasAlumno($idPeriodo, $curso[0]['idCurso'], $idAlumno);

        //  = Alumno::getAlumnoCurso($idPeriodo, $idAlumno);
        // $notas = Alumno::getAlumnoCurso($idPeriodo, $idAlumno);


        $rbdEstablecimiento = $establecimiento[0]['rbd'];
        $data = array();
        if ($rbdEstablecimiento === '1855-4') { //Francia

            array_push($data, array(
                'alumno' => $alumno[0],
                'establecimiento' => $establecimiento[0],
                'curso' => $curso[0],
                'asignaturas' => $asignaturas,
                'notas' => $notas,
            ));
            $html = $this->downloadPDFEscuelaFrancia($data[0]);
        }

        $alumno = $data[0]['alumno']['numLista'].'-'.$data[0]['alumno']['nombres'].' '.$data[0]['alumno']['primerApellido'].' '.$data[0]['alumno']['segundoApellido'].' - '.$data[0]['curso']['nombre'];
        // print($html);
        return $this->downloadPDF($html, $tipo, $alumno);
    }


    function downloadPDF ($html, $tipo, $alumno) {
        ini_set('max_execution_time', 300);
        ini_set("memoria_limite","512M");
        $pdf = PDF::loadHTML($html);

        $pdf->setPaper('A4');

        // $nombrePdf = $compra->rutProveedor.'-'.$compra->nombreProveedor.'-num_'.$correlativo.'.pdf';
        $nombrePdf = $alumno.' - informe_hogar.pdf';

        if ($tipo == 'download') {
            return $pdf->download($nombrePdf);
        } else if ($tipo == 'read') {
            return $pdf->stream($nombrePdf);
        }
    }


    function downloadPDFEscuelaFrancia($data)
    {

        // return $data;
        $style = $this->style();

        $htmlEncabezado = $this->encabezado(
            $data['establecimiento']
        );

        $htmlDatosAlumno = $this->datosAlumno(
            $data['alumno'],
            $data['curso']
        );

        $htmlTablaNotas = $this->notas(
            $data['asignaturas']
            , $data['notas']
        );

        // $htmlProductos = $this->productos(
        //     $compraProductos
        //     , $compra->tiempoGarantia
        //     , $compra->observaciones
        // );

        // $htmlCondicionesVenta = $this->condicionesVenta(
        //     $compra->lugarEntrega
        //     , $compra->condicionPago
        //     , $compra->fechaEntrega
        // );

        // $htmlTotales = $this->totales(
        //     $compra->valorTotal
        // );

        // $htmlCondicionesGenerales = $this->condicionesGenerales();
        // $htmlTimbre = $this->timbre();

        // $htmlFooter = $this->footer(
        //     $compra->nombreUsuario
        // );



        // // header
        // '. $htmlEncabezado.'
        // '. $htmlFecha .'
        // '. $htmlProveedor .'
        // //main
        // '. $htmlProductos .'
        $nivelLogro = '../storage/app/public/images/nivelLogro_'.$data['establecimiento']['rbd'].'.png';
        $footer = '../public/storage/images/diseño_'.$data['establecimiento']['rbd'].'.png';
        return '
        <html>
            <header>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <style>
                    '. $style .'
                </style>
            </header>
            <body style="font-family:sans-serif;">
                <header>
                    '. $htmlEncabezado .'
                </header>
                <div class="main">
                    <div class="text-center">
                        <h2>Informe al Hogar</h2>
                    </div>

                    <div class="datos-alumno">
                        '.$htmlDatosAlumno.'
                    </div>

                    <div class="mensaje " style="margin-top: 20px;">
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

                        <img src="'. $nivelLogro .'" alt="Nivel de Logro" width="700" class="nivel-logro"/>

                        <p class="text-justify txt-msje">
                        El promedio será representado en una calificación con el fin
                        de identificar las asignaturas aprobadas o reprobadas.
                        </p>
                    </div>

                    <div >
                    </div>

                    <div class="mensaje">
                    </div>

                    <div style="margin-top: 120px;">
                        '.$htmlTablaNotas.'
                    </div>
                </div>
                <div class="div-footer">
                    <table class="sin-borde">
                        <tbody>
                            <tr>
                                <td class="sin-borde">

                                <p class="text-justify txt-msje">
                                OBSERVACIONES:
                                _________________________________________________________________________________________
                                _________________________________________________________________________________________
                                _________________________________________________________________________________________
                                _________________________________________________________________________________________
                                _________________________________________________________________________________________
                                _________________________________________________________________________________________
                                </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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

                // <footer>
                //     <img src="'. $footer .'" alt="Nivel de Logro" width="900" z-index="100000"/>
                // </footer>
        // die();


    }
}
