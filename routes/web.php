<?php

use App\Http\Controllers\ActuacioneController;
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\AnexoController;
use App\Http\Controllers\AuditController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\PlantillaController;
use App\Http\Controllers\TipoplantillaController;
use App\Http\Controllers\TipoprocesoController;
use App\Http\Controllers\TramiteController;
use App\Http\Controllers\ClaseprocesoController;
use App\Http\Controllers\NaturalezaController;
use App\Http\Controllers\JuzgadoController;
use App\Http\Controllers\CiudadeController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\PreliminareController;
use App\Http\Controllers\ProcesoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('auth.login');
});

Route::group(['middleware'=>['auth']],function(){
    Route::resource('roles',RolController::class);
    Route::resource('usuarios',UsuarioController::class);
    Route::resource('plantillas',PlantillaController::class);
    Route::resource('tipoplantillas',TipoplantillaController::class);    
    Route::resource('tipoprocesos',TipoprocesoController::class);
    Route::resource('tramites',TramiteController::class);
    Route::resource('claseprocesos',ClaseprocesoController::class);
    Route::resource('naturalezas',NaturalezaController::class);
    Route::resource('juzgados',JuzgadoController::class);
    Route::resource('ciudades',CiudadeController::class);
    Route::resource('clientes',ClienteController::class);
    Route::resource('procesos',ProcesoController::class);
    Route::resource('actuaciones',ActuacioneController::class);
    Route::resource('alertas',AlertaController::class);
    Route::resource('anexos',AnexoController::class);
    Route::resource('preliminares',PreliminareController::class);
    Route::resource('auditoria',AuditController::class);
   

    Route::get('procesos/selectpreliminar/{id_cliente}', 'App\Http\Controllers\ProcesoController@selectpreliminar');
    Route::get('procesos/{id_proceso}/selectpreliminar/{id_cliente}', 'App\Http\Controllers\ProcesoController@selectpreliminar');

});

Auth::routes(); 
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::group(['middleware' => 'auth'], function () {
	Route::resource('user', 'App\Http\Controllers\UserController', ['except' => ['show']]);
	Route::get('profile', ['as' => 'profile.edit', 'uses' => 'App\Http\Controllers\ProfileController@edit']);
	Route::put('profile', ['as' => 'profile.update', 'uses' => 'App\Http\Controllers\ProfileController@update']);
	Route::put('profile/password', ['as' => 'profile.password', 'uses' => 'App\Http\Controllers\ProfileController@password']);
	Route::get('{page}', ['as' => 'page.index', 'uses' => 'App\Http\Controllers\PageController@index']);
});
