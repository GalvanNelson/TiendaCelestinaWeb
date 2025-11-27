<?php

use App\Http\Controllers\PagoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rutas protegidas con autenticación (web session para Inertia)
Route::middleware(['web', 'auth'])->group(function () {
    // Listar métodos de pago habilitados
    Route::get('/pagos/metodos-habilitados', [PagoController::class, 'listarMetodosHabilitados']);

    // Generar QR
    Route::post('/pagos/qr/generar', [PagoController::class, 'pagarConQR']);

    // Verificar estado del pago
    Route::post('/pagos/qr/verificar', [PagoController::class, 'verificarPago']);

    // Confirmar pago manualmente
    Route::post('/pagos/qr/confirmar', [PagoController::class, 'confirmarPagoQR']);
});

// Callback de Pago Fácil (sin autenticación - webhook público)
Route::post('/pagos/callback', [PagoController::class, 'callbackPagoQR']);
