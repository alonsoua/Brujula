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

use App\Http\Controllers\EstablecimientoController;
use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\AvanceAprendizajeController;
use App\Http\Controllers\PuntajeIndicadorController;
use App\Http\Controllers\NotasConversionController;
use App\Http\Controllers\NotasController;
use App\Http\Controllers\InformeHogarController;
// use App\Http\Controllers\Auth\SignInController;
// use App\Http\Controllers\Auth\SignOutController;
// use App\Http\Controllers\Auth\MeController;

use Illuminate\Http\Request;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::group([

//     'middleware' => 'api',
//     'prefix' => 'auth'

// ], function ($router) {
//     Route::post('signin', 'App\Http\Controllers\AuthController@login');
//     Route::post('logout', 'App\Http\Controllers\AuthController@logout');
//     Route::post('refresh', 'App\Http\Controllers\AuthController@refresh');
//     Route::post('me', 'App\Http\Controllers\AuthController@me');
//     Route::post('register', 'App\Http\Controllers\AuthController@register');

// });
// Route::group([

//     'middleware' => 'api',
//     'prefix' => 'inquilino'

// ], function ($router) {
//     Route::post('add', 'App\Http\Controllers\InquilinosController@registered');
//     // Route::post('logout', 'App\Http\Controllers\InquilinosController@logout');
//     // Route::post('refresh', 'App\Http\Controllers\InquilinosController@refresh');
//     // Route::post('me', 'App\Http\Controllers\InquilinosController@me');
//     // Route::post('register', 'App\Http\Controllers\InquilinosController@register');

// });

// Autenticación
Route::group([
    'prefix' => 'auth',
    'namespace' =>  'App\Http\Controllers\Auth'
    // 'middleware' => 'api',
], function () {
    Route::post('signin', 'SignInController');
    Route::post('signout', 'SignOutController');
    Route::get('me', 'MeController');
});

/*
*Route::get('migrate', function() {
*    Artisan::call('migrate');
*});
*/

/* Funcion para subir relaciones entre
* Asignaturas
* Unidades
* Objetivos
* Indicadores
* Actividades
*/
// Route::post('/jsonObjetivos', [JsonObjetivosController::class, 'store']);

// * Tipo Enseñanza
Route::get('/tipoEnsenanza', [TipoEnsenanzaController::class, 'index']);

// * Grados
Route::get('/grados', [GradoController::class, 'index']);

// * Asignatura
Route::get('/asignaturas', [AsignaturaController::class, 'index']);
Route::get('/asignaturas/activos', [AsignaturaController::class, 'getActivos']);
Route::get(
    '/asignaturas/activos/grado/{idgrado}',
    [AsignaturaController::class, 'getActivosGrado']
);

// * Eje
Route::get('/ejes/asignatura/{idAsignatura}',
    [EjeController::class, 'getEjesPorAsignatura']
);
Route::get('/ejes/asignatura/distinct/{idAsignatura}',
    [EjeController::class, 'getEjesAsignatura']
);
Route::post('/ejes', [EjeController::class, 'store']);

// * Objetivos
Route::get('/objetivos', [ObjetivoController::class, 'getObjetivos']);
Route::get('/objetivos/asignatura/{idAsignatura}/{idPeriodo}', [ObjetivoController::class, 'getObjetivosActivosAsignatura']);
Route::get('/objetivos/betwen/{idCursoInicio}/{idCursoFin}', [ObjetivoController::class, 'getObjetivosBetwen']);
Route::put('/objetivos/estado/{id}', [ObjetivoController::class, 'updateEstadoMinisterio']);
Route::put('/objetivos/priorizacion/interna/{id}', [ObjetivoController::class, 'updatePriorizacionInterna']);

// Route::get('/objetivos/establecimiento/{id}', [ObjetivoController::class, 'getObjetivosEstablecimiento']);

// * Objetivos PERSONALIZADOS
Route::post('/objetivos/personalizados', [ObjetivoController::class, 'storePersonalizado']);
Route::put('/objetivos/personalizados/{id}', [ObjetivoController::class, 'updatePersonalizado']);
Route::put('/objetivos/personalizados/priorizacion/{id}', [ObjetivoController::class, 'updatePriorizacionPersonalizado']);
Route::put('/objetivos/personalizados/estado/{id}', [ObjetivoController::class, 'updateEstadoPersonalizado']);



// * Indicadores
Route::get('/indicadores/objetivo/{idObjetivo}/{tipo}',
    [IndicadorController::class, 'getIndicadoresObjetivo']
);

Route::get('/indicadores/personalizados/{idObjetivo}',
    [IndicadorController::class, 'getIndicadoresPersonalizados']
);

// * Diagnostico pie
Route::get('/diagnosticos', [DiagnosticoPieController::class, 'index']);
// * Prioritario
Route::get('/prioritarios', [PrioritarioController::class, 'index']);

// * NotasConversion
Route::get('/notasConversion/{cantidadIndicadores}/{puntajeObtenido}',
    [NotasConversionController::class, 'getNotasConversion']
);

// * Notas
Route::get('/notas/getNotasAsignatura/{idPeriodo}/{idCurso}/{idAsignatura}',
    [NotasController::class, 'getNotasAsignatura']
);

Route::get('/notas/getAllNotasCurso/{idPeriodo}/{idCurso}',
    [NotasController::class, 'getAllNotasCurso']
);

Route::get('/notas/calcularNota/{idAlumno}/{idCurso}/{idAsignatura}/{idPeriodo}/{idObjetivo}',
    [NotasController::class, 'calcularNota']
);
Route::get('/notas/calcularNotaCurso/{idCurso}/{idAsignatura}/{idPeriodo}/{idObjetivo}',
    [NotasController::class, 'calcularNotaCurso']
);

// * Roles
Route::get('/roles', [RolController::class, 'index']);

// * Periodos
Route::get('/periodos', [PeriodoController::class, 'index']);

// * Usuarios
Route::get('/usuarios', [UserController::class, 'index']);
Route::get('/usuarios/docentes', [UserController::class, 'getDocentesActivos']);
Route::get('/usuarios/docente/asignaturas/{id}/{idEstablecimiento}', [UserController::class, 'getDocenteAsignaturas']);
Route::post('/usuarios', [UserController::class, 'store']);
Route::put('/usuarios/{id}', [UserController::class, 'update']);
Route::put('/usuarios/vistas/{id}', [UserController::class, 'updateVistas']);
Route::delete('/usuarios/{id}', [UserController::class, 'destroy']);
// Route::put('/usuarios/password/{id}', [UserController::class, 'updatePassword']);


// * Alumnos
Route::get('/alumnos', [AlumnoController::class, 'index']);
Route::get('/alumnos/periodo', [AlumnoController::class, 'getAlumnosPeriodo']);
Route::post('/alumnos', [AlumnoController::class, 'store']);
Route::get('/alumnos/curso/{idCurso}',
    [AlumnoController::class, 'getAlumnosCurso']
);
Route::put('/alumnos/{id}', [AlumnoController::class, 'update']);
Route::delete('/alumnos/{id}', [AlumnoController::class, 'destroy']);

// * Establecimientos
Route::get('/establecimientos', [EstablecimientoController::class, 'index']);
Route::get('/establecimientos/activos', [EstablecimientoController::class, 'getActivos']);
Route::post('/establecimientos', [EstablecimientoController::class, 'store']);
Route::put('/establecimientos/{id}', [EstablecimientoController::class, 'update']);
Route::put('/establecimientos/periodoActivo/{id}',
    [EstablecimientoController::class, 'updatePeriodoActivo']
);
// Route::delete('/establecimientos/{id}', [EstablecimientoController::class, 'destroy']);

// * Indicador Personalizado
Route::get('/indicador/personalizado/', [IndicadorPersonalizadoController::class, 'index']);
Route::get('/indicador/personalizado/aprobados/{idObjetivo}/{periodoActual}/{idCurso}',
    [IndicadorPersonalizadoController::class, 'getIndicadorPersonalizadosAprobados']
);
Route::get('/indicador/personalizado/{idObjetivo}/{periodoActual}/{idCurso}',
    [IndicadorPersonalizadoController::class, 'getIndicadorPersonalizados']
);
Route::post('/indicador/personalizado/', [IndicadorPersonalizadoController::class, 'store']);
Route::put('/indicador/personalizado/{id}', [IndicadorPersonalizadoController::class, 'update']);
Route::delete('/indicador/personalizado/{id}', [IndicadorPersonalizadoController::class, 'destroy']);


// * Cursos
Route::get('/cursos', [CursoController::class, 'index']);
Route::get('/cursos/activos', [CursoController::class, 'getActivos']);
Route::get(
    '/cursos/activos/establecimiento/{idestablecimiento}',
    [CursoController::class, 'getActivosEstablecimiento']
);
Route::post('/cursos', [CursoController::class, 'store']);
Route::put('/cursos/{id}', [CursoController::class, 'update']);

// * AvanceAprendizaje
// Docentes
Route::get(
    '/avances/tipoEnseñanza/{idusuarioestablecimiento}',
    [AvanceAprendizajeController::class, 'getTipoEnseñanza']
);
Route::get(
    '/avances/curso/activo/{idusuarioestablecimiento}',
    [AvanceAprendizajeController::class, 'getCursoActivo']
);
Route::get(
    '/avances/asignatura/activa/{idusuarioestablecimiento}',
    [AvanceAprendizajeController::class, 'getAsignaturaActiva']
);
// Director - Inspectores
Route::get(
    '/avances/curso/establecimiento/activo/{idEstablecimiento}',
    [AvanceAprendizajeController::class, 'getCursoEstablecimientoActivo']
);
Route::get(
    '/avances/asignatura/curso/activa/{idCurso}',
    [AvanceAprendizajeController::class, 'getAsignaturaCursoActiva']
);

// * Puntaje Indicador
Route::get(
    '/puntajes/indicadores/{idperiodo}/{idcurso}/{idasignatura}/{idObjetivo}/{tipo}',
    [PuntajeIndicadorController::class, 'getPuntajesIndicadores']
);

Route::put(
    '/puntajes/{idPuntaje}',
    [PuntajeIndicadorController::class, 'update']
);


// * Puntaje Indicador Transformacion
Route::get(
    '/puntajes/indicadores/transformacion',
    [PuntajeIndicadorController::class, 'getPuntajesIndicadoresTransformacion']
);

// * INFORME HOGAR

Route::get('/informe/hogar/{idPeriodo}/{idAlumno}/{tipo}', [InformeHogarController::class, 'createPDF']);