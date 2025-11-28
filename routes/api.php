<?php

use App\Http\Controllers\PagoController;
use App\Http\Controllers\PagoFacilWebhookController;
use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Payment Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {

    // Obtener métodos de pago disponibles
    Route::get('/payment/methods', [PaymentController::class, 'getPaymentMethods']);

    // Generar QR para pago de venta
    Route::post('/ventas/{venta}/generate-payment-qr', [PaymentController::class, 'generatePaymentQR']);

    // Generar QR para pago de cuota específica
    Route::post('/cuotas/{cuota}/generate-payment-qr', [PaymentController::class, 'generateCuotaPaymentQR']);

    // Consultar estado de transacción
    Route::get('/transactions/{transaction}/status', [PaymentController::class, 'checkTransactionStatus']);

});

/*
|--------------------------------------------------------------------------
| PagoFácil Webhook (Sin autenticación - es llamado por PagoFácil)
|--------------------------------------------------------------------------
*/

Route::post('/pagofacil/webhook', [PagoFacilWebhookController::class, 'handleWebhook'])
    ->name('pagofacil.webhook');
