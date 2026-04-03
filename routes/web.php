<?php

use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/terminos', [PortalController::class, 'terms'])->name('terms');
    Route::get('/privacidad', [PortalController::class, 'privacy'])->name('privacy');
    Route::get('/{token}', [PortalController::class, 'show'])->name('show');
    Route::post('/{token}/aceptar', [PortalController::class, 'accept'])->name('accept');
});
