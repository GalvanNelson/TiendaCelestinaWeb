<?php

use App\Http\Controllers\PagoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rutas protegidas con autenticación (web session para Inertia)
Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/pagos/qr/generar', [PagoController::class, 'pagarConQR'])
        ->name('pagos.qr.generar');  // ⭐ IMPORTANTE: El nombre

    Route::post('/pagos/qr/verificar', [PagoController::class, 'verificarPago'])
        ->name('pagos.qr.verificar');

    Route::post('/pagos/qr/confirmar', [PagoController::class, 'confirmarPagoQR'])
        ->name('pagos.qr.confirmar');
});

// Callback de Pago Fácil (sin autenticación - webhook público)
Route::post('/pagos/callback', [PagoController::class, 'callbackPagoQR'])
    ->name('pagos.callback');
