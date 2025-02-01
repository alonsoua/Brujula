<?php

// TABLAS PADRE
use App\Http\Controllers\JsonObjetivosController;
use App\Http\Controllers\TipoEnsenanzaController;
use App\Http\Controllers\GradoController;
use App\Http\Controllers\AsignaturaController;
use App\Http\Controllers\EjeController;
use App\Http\Controllers\ObjetivoController;
use App\Http\Controllers\IndicadorController;
use App\Http\Controllers\IndicadorPersonalizadoController;
use App\Http\Controllers\DiagnosticoPieController;
use App\Http\Controllers\PrioritarioController;

use App\Http\Controllers\DashController;
use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\AvanceAprendizajeController;
use App\Http\Controllers\PuntajeIndicadorController;
use App\Http\Controllers\NotasConversionController;
use App\Http\Controllers\NotasController;
use App\Http\Controllers\InformeHogarController;
use App\Http\Controllers\InformesController;
use App\Http\Controllers\Master\EstablecimientoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/* Funcion para subir relaciones entre
* Asignaturas
* Unidades
* Objetivos
* Indicadores
* Actividades
*/
// Route::post('/jsonObjetivos', [JsonObjetivosController::class, 'store']);

/* MIGRATIONS
*Route::get('migrate', function() {
*    Artisan::call('migrate');
*});
*/

Route::prefix('bru')->group(function () {

    // MASTER
    Route::prefix('master')->namespace('App\Http\Controllers\Master')->group(function () {
        // Route::post('/login', 'AuthController@login');
        Route::post('/establecimiento', [EstablecimientoController::class, 'store']);

        Route::middleware(['auth:master', 'tenant'])->group(function () {
            // Route::post(
            //     '/me',
            //     'AuthController@authme'
            // );
            // Route::get('/establecimientos', [EstablecimientoController::class, 'index']);
            // Route::get('/establecimientos/activos', [EstablecimientoController::class, 'getActivos']);
            // Route::post('/establecimiento', [EstablecimientoController::class, 'store']);
            // Route::put('/establecimientos/{id}', [EstablecimientoController::class, 'update']);
            // Route::put('/establecimientos/periodoActivo/{id}', [EstablecimientoController::class, 'updatePeriodoActivo']);

            // Route::post('/logout', 'AuthController@logout');

            // // * USUARIOS
            // Route::get('/usuarios', 'UsuariosController@index');
            // Route::get('/usuario/{id}', 'UsuariosController@show');
            // Route::post('/usuario', 'UsuariosController@store');
            // Route::put('/usuario/{id}', 'UsuariosController@update');
            // Route::delete('/usuario/{id}', 'UsuariosController@destroy');
            // Route::put('/usuario/estado/{id}', 'UsuariosController@updateEstado');

            // // * ESTABLECIMIENTOS USUARIOS
            // Route::get('/establecimiento/usuarios/{num_items}', 'EstablecimientosUsuariosController@index');
            // Route::get(
            //     '/establecimiento/usuario/{id}',
            //     'EstablecimientosUsuariosController@show'
            // );
            // Route::post('/establecimiento/usuario', 'EstablecimientosUsuariosController@store');
            // Route::put('/establecimiento/usuario/{id}', 'EstablecimientosUsuariosController@update');
            // Route::delete('/establecimiento/usuario/{id}', 'EstablecimientosUsuariosController@destroy');
            // Route::put('/establecimiento/usuario/estado/{id}', 'EstablecimientosUsuariosController@updateEstado');

            // // * ROLES
            // Route::get('/rol', 'RolesController@index');
            // Route::get('/rol/{id}', 'RolesController@get');
            // Route::post('/rol', 'RolesController@store');
            // Route::put('/rol/{id}', 'RolesController@update');
            // Route::delete('/rol/{id}', 'RolesController@destroy');
        });
    });

    // ESTABLECIMIENTOS
    Route::prefix('estab')->namespace('App\Http\Controllers')->group(function () {
        Route::post('/login', 'Auth\AuthController@login');

        Route::middleware(['auth:establecimiento', 'tenant'])->group(function () {

            Route::get('auth/me', 'Auth\MeController@me');

            // Route::post('auth/logout', 'Auth\AuthController@logout');

            // * Enpoints padre (master)
            // * Periodos
            Route::get('/periodos', [PeriodoController::class, 'index']);
            // * Roles
            Route::get('/roles', [RolController::class, 'index']);
            // * Tipos Enseñanza
            Route::get('/tipoEnsenanza', [TipoEnsenanzaController::class, 'index']);
            // * Grados
            Route::get('/grados/porIdNivel/{idNivel}', [GradoController::class, 'getPorIdNivel']); //(idTipoEnseñanza)
            // Route::get('/grados', [GradoController::class, 'index']);

            // * Endpoints establecimiento (tenant)
            // * Cursos
            Route::get('/cursos', [CursoController::class, 'index']);
            // Route::get('/cursos/activos', [CursoController::class, 'getActivos']);
            // Route::get('/cursos/activos/establecimiento/{idestablecimiento}', [CursoController::class, 'getActivosEstablecimiento']);
            // Route::get('/cursos/usuario/{idPeriodoHistorico}', [CursoController::class, 'getCursosUsuario']);
            Route::post('/cursos', [CursoController::class, 'store']);
            Route::put('/cursos/{id}', [CursoController::class, 'update']);
            // Route::put('/cursos/ordenar/lista/{idCurso}', [CursoController::class, 'ordenarLista']);
            

            // // * Usuarios
            // Route::get('/usuarios', [UserController::class, 'index']);
            // Route::get('/usuarios/docentes', [
            //     UserController::class,
            //     'getDocentesActivos'
            // ]);
            // Route::get('/usuarios/docente/asignaturas/{id}/{idEstablecimiento}', [UserController::class, 'getDocenteAsignaturas']);
            // Route::post('/usuarios', [UserController::class, 'store']);
            // Route::put('/usuarios/{id}', [UserController::class, 'update']);
            // Route::put('/usuarios/vistas/{id}', [UserController::class, 'updateVistas']);
            // Route::delete('/usuarios/{id}', [UserController::class, 'destroy']);

            // // * Establecimientos
            // Route::put('/establecimientos/{id}', [EstablecimientoController::class, 'update']);
            // Route::put('/establecimientos/periodoActivo/{id}', [EstablecimientoController::class, 'updatePeriodoActivo']);

            

            // // * Alumnos
            Route::get('/alumnos', [AlumnoController::class, 'index']);
            // Route::get('/alumnos/periodo', [AlumnoController::class, 'getAlumnosPeriodo']);
            // Route::post('/alumnos', [AlumnoController::class, 'store']);
            // Route::get('/alumnos/curso/{idCurso}', [AlumnoController::class, 'getAlumnosCurso']);
            // Route::put('/alumnos/{id}', [AlumnoController::class, 'update']);
            // Route::delete('/alumnos/{id}', [AlumnoController::class, 'destroy']);
            // * Imports
            // Route::post('/alumnos/import', [AlumnoController::class, 'importAlumnos']);
            Route::post('/alumnos/importCSV', [AlumnoController::class, 'importAlumnosCSV']);

            // // * Diagnostico pie
            // Route::get('/diagnosticos', [DiagnosticoPieController::class, 'index']);

            // // * Prioritario
            // Route::get('/prioritarios', [PrioritarioController::class, 'index']);

            

            // // * Asignaturas
            // Route::get(
            //     '/asignaturas',
            //     [AsignaturaController::class, 'index']
            // );
            // Route::get('/asignaturas/activos', [AsignaturaController::class, 'getActivos']);
            // Route::get('/asignaturas/getDocentesAsignaturas/{idPeriodo}', [AsignaturaController::class, 'getDocentesAsignaturas']);
            // Route::get('/asignaturas/activos/grado/{idgrado}', [AsignaturaController::class, 'getActivosGrado']);
            // Route::get('/asignaturas/usuario/{idCurso}/{idPeriodoHistorico}', [AsignaturaController::class, 'getAsignaturasUsuario']);
            // Route::get('/asignaturas/curso/{idCurso}/{idPeriodoHistorico}', [AsignaturaController::class, 'getAsignaturasCurso']);

            // // * Ejes
            // Route::get('/ejes/asignatura/{idAsignatura}', [EjeController::class, 'getEjesPorAsignatura']);
            // Route::get('/ejes/asignatura/distinct/{idAsignatura}', [EjeController::class, 'getEjesAsignatura']);
            // Route::post('/ejes', [EjeController::class, 'store']);

            // // * Objetivos
            // Route::get('/objetivos', [ObjetivoController::class, 'getObjetivos']);
            // Route::get('/objetivos/asignatura/{idAsignatura}/{idPeriodo}', [ObjetivoController::class, 'getObjetivosActivosAsignatura']);
            // Route::get('/objetivos/betwen/{idCursoInicio}/{idCursoFin}', [ObjetivoController::class, 'getObjetivosBetwen']);
            // Route::put('/objetivos/estado/{id}', [ObjetivoController::class, 'updateEstadoMinisterio']);
            // Route::put('/objetivos/priorizacion/interna/{id}', [ObjetivoController::class, 'updatePriorizacionInterna']);
            // Route::post('/objetivos/trabajados', [ObjetivoController::class, 'objetivosTrabajados']);

            // // * Objetivos PERSONALIZADOS
            // Route::post('/objetivos/personalizados', [ObjetivoController::class, 'storePersonalizado']);
            // Route::put('/objetivos/personalizados/{id}', [ObjetivoController::class, 'updatePersonalizado']);
            // Route::put('/objetivos/personalizados/priorizacion/{id}', [ObjetivoController::class, 'updatePriorizacionPersonalizado']);
            // Route::put('/objetivos/personalizados/estado/{id}', [ObjetivoController::class, 'updateEstadoPersonalizado']);

            // // * Indicadores
            // Route::get('/indicadores/objetivo/{idObjetivo}/{tipo}', [IndicadorController::class, 'getIndicadoresObjetivo']);
            // Route::get('/indicadores/personalizados/{idObjetivo}', [IndicadorController::class, 'getIndicadoresPersonalizados']);

            // // * Notas
            // Route::get('/notas/getNotasAsignatura/{idPeriodo}/{idCurso}/{idAsignatura}', [NotasController::class, 'getNotasAsignatura']);
            // Route::get('/notas/getAllNotasCurso/{idPeriodo}/{idCurso}', [NotasController::class, 'getAllNotasCurso']);
            // Route::get('/notas/calcularNota/{idAlumno}/{idCurso}/{idAsignatura}/{idPeriodo}/{idObjetivo}', [NotasController::class, 'calcularNota']);
            // Route::get('/notas/getAll/{idPeriodo}/{idCurso}', [NotasController::class, 'getAll']);
            // Route::get('/notas/calcularNotaCurso/{idCurso}/{idAsignatura}/{idPeriodo}/{idObjetivo}', [NotasController::class, 'calcularNotaCurso']);
            // Route::post('/notas/updateNota/', [NotasController::class, 'updateNota']);

            // // * NotasConversion
            // Route::get('/notasConversion/{cantidadIndicadores}/{puntajeObtenido}', [NotasConversionController::class, 'getNotasConversion']);

            // // * Indicador Personalizado
            // Route::get('/indicador/personalizado/', [IndicadorPersonalizadoController::class, 'index']);
            // Route::get('/indicador/personalizado/aprobados/{idObjetivo}/{periodoActual}/{idCurso}/{tipo}', [IndicadorPersonalizadoController::class, 'getIndicadorPersonalizadosAprobados']);
            // Route::get('/indicador/personalizado/{idObjetivo}/{periodoActual}/{idCurso}/{tipo}', [IndicadorPersonalizadoController::class, 'getIndicadorPersonalizados']);
            // Route::post('/indicador/personalizado/', [IndicadorPersonalizadoController::class, 'store']);
            // Route::put('/indicador/personalizado/{id}', [IndicadorPersonalizadoController::class, 'update']);
            // Route::delete('/indicador/personalizado/{id}', [IndicadorPersonalizadoController::class, 'destroy']);

            // // * Avances Aprendizaje
            // Route::get('/avances/tipoEnseñanza/{idusuarioestablecimiento}', [AvanceAprendizajeController::class, 'getTipoEnseñanza']);

            // // * Puntaje Indicador
            // Route::get('/puntajes/indicadores/{idperiodo}/{idcurso}/{idasignatura}/{idObjetivo}/{tipo}', [PuntajeIndicadorController::class, 'getPuntajesIndicadores']);
            // Route::get('/puntajes/resumen/{idperiodo}/{idcurso}/{idasignatura}', [PuntajeIndicadorController::class, 'getNotasResumen']);
            // Route::put('/puntajes/{idPuntaje}', [PuntajeIndicadorController::class, 'update']);

            // // * Puntaje Indicador Transformacion
            // Route::get('/puntajes/indicadores/transformacion', [PuntajeIndicadorController::class, 'getPuntajesIndicadoresTransformacion']);

            // // * INFORME HOGAR
            // Route::get('/informe/hogar/{idAlumno}/{tipo}/{tipoInforme}', [InformeHogarController::class, 'createPDF']);
            // Route::post('/informes/resumenAnualPdf', [InformesController::class, 'resumenAnualPdf']);
            // Route::get('/notas/update/notas/{idCurso}/{idGrado}', [NotasController::class, 'updateNotasScript']);

            // // * DASH
            // // ? CONEXIÓN BRÚJULA > LD
            // Route::get('/dash/conexionLd/getLogs/{idPeriodo}', [DashController::class, 'getLdConexions']);
            // Route::post('/dash/conexionLd/addLog', [DashController::class, 'addLdConexion']);
        });
    });
});
