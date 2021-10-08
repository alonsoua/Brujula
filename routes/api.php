<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\EstablecimientoController;
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

Route::group([

    'middleware' => 'api',
    'prefix' => 'inquilino'

], function ($router) {
    Route::post('add', 'App\Http\Controllers\InquilinosController@registered');
    // Route::post('logout', 'App\Http\Controllers\InquilinosController@logout');
    // Route::post('refresh', 'App\Http\Controllers\InquilinosController@refresh');
    // Route::post('me', 'App\Http\Controllers\InquilinosController@me');
    // Route::post('register', 'App\Http\Controllers\InquilinosController@register');

});

// Usuarios
Route::get('/usuarios', [UserController::class, 'index']);
// Route::get('/usuarios/activos', [UserController::class, 'getActivos']);
// Route::post('/usuarios', [UserController::class, 'store']);
// Route::put('/usuarios/estado/{id}', [UserController::class, 'updateEstado']);
// Route::put('/usuarios/{id}', [UserController::class, 'update']);
// Route::put('/usuarios/password/{id}', [UserController::class, 'updatePassword']);
// Route::delete('/usuarios/{id}', [UserController::class, 'destroy']);

// Periodos
Route::get('/periodos', [PeriodoController::class, 'index']);

// Establecimientos
Route::get('/establecimientos', [EstablecimientoController::class, 'index']);
Route::post('/establecimientos', [EstablecimientoController::class, 'store']);
Route::put('/establecimientos/{id}', [EstablecimientoController::class, 'update']);
Route::get('/establecimientos/activos', [EstablecimientoController::class, 'getActivos']);
Route::put('/establecimientos/periodoActivo/{id}', [EstablecimientoController::class, 'updatePeriodoActivo']);
// Route::delete('/establecimientos/{id}', [EstablecimientoController::class, 'destroy']);
// Route::put('/establecimientos/password/{id}', [EstablecimientoController::class, 'updatePassword']);