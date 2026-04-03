<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\WompiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Google Auth
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

// Portal del Cliente
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/terminos', [PortalController::class, 'terms'])->name('terms');
    Route::get('/privacidad', [PortalController::class, 'privacy'])->name('privacy');
    Route::get('/{token}', [PortalController::class, 'show'])->name('show');
    Route::post('/{token}/aceptar', [PortalController::class, 'accept'])->name('accept');
});

// Wompi Payments
Route::middleware('auth')->group(function () {
    Route::post('/wompi/checkout', [WompiController::class, 'checkout'])->name('wompi.checkout');
    Route::get('/wompi/callback', [WompiController::class, 'callback'])->name('wompi.callback');
});
Route::post('/wompi/webhook', [WompiController::class, 'webhook'])->name('wompi.webhook');
