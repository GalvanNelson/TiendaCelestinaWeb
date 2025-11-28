<?php

namespace App\Http\Controllers;

use App\Models\CuentaPorCobrar;
use App\Models\Pago;
use App\Models\PagoFacilTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PagoFacilWebhookController extends Controller
{
    /**
     * Recibir notificación de PagoFácil
     *
     * Estructura esperada:
     * {
     *   "PedidoID": "V-2025-001-1735689012",
     *   "Fecha": "2025-01-01",
     *   "Hora": "14:30:00",
     *   "MetodoPago": "QR BCP",
     *   "Estado": "Completado"
     * }
     */
    public function handleWebhook(Request $request)
    {
        // Log de la notificación recibida
        Log::info('PagoFácil Webhook Received', [
            'payload' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        try {
            // Validar payload
            $validated = $request->validate([
                'PedidoID' => 'required|string',
                'Fecha' => 'required|string',
                'Hora' => 'required|string',
                'MetodoPago' => 'required|string',
                'Estado' => 'required|string'
            ]);

            // Buscar la transacción
            $transaction = PagoFacilTransaction::where('company_transaction_id', $validated['PedidoID'])
                ->firstOrFail();

            // Si ya fue procesada, devolver éxito
            if ($transaction->status === 'completed') {
                Log::info('Transaction already processed', [
                    'transaction_id' => $transaction->id,
                    'company_id' => $validated['PedidoID']
                ]);

                return $this->successResponse('Transacción ya procesada');
            }

            DB::beginTransaction();

            // Mapear estado
            $estado = strtolower($validated['Estado']);
            $newStatus = match(true) {
                str_contains($estado, 'completado') || str_contains($estado, 'exitoso') => 'completed',
                str_contains($estado, 'fallido') || str_contains($estado, 'rechazado') => 'failed',
                str_contains($estado, 'expirado') => 'expired',
                default => 'pending'
            };

            // Actualizar transacción
            $transaction->update([
                'status' => $newStatus,
                'payment_date' => now()->parse($validated['Fecha']),
                'payment_time' => now()->parse($validated['Fecha'] . ' ' . $validated['Hora']),
                'payment_method_name' => $validated['MetodoPago'],
                'response_data' => array_merge($transaction->response_data ?? [], [
                    'webhook_data' => $validated,
                    'webhook_received_at' => now()->toIso8601String()
                ])
            ]);

            // Si el pago fue exitoso, procesarlo
            if ($newStatus === 'completed') {
                $this->procesarPagoExitoso($transaction, $validated);
            }

            DB::commit();

            Log::info('Webhook processed successfully', [
                'transaction_id' => $transaction->id,
                'new_status' => $newStatus
            ]);

            return $this->successResponse('Notificación procesada correctamente');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Transaction not found in webhook', [
                'pedido_id' => $request->input('PedidoID')
            ]);

            return $this->errorResponse('Transacción no encontrada', 404);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error processing webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            return $this->errorResponse('Error procesando notificación', 500);
        }
    }

    /**
     * Procesar pago exitoso
     */
    private function procesarPagoExitoso(PagoFacilTransaction $transaction, array $webhookData): void
    {
        $venta = $transaction->venta;

        // Crear registro de pago
        $pago = Pago::create([
            'venta_id' => $venta->codigo_venta,
            'monto' => $transaction->amount,
            'fecha_pago' => now()->parse($webhookData['Fecha'] . ' ' . $webhookData['Hora']),
            'metodo_pago' => 'qr',
            'referencia' => $transaction->pagofacil_transaction_id ?? $transaction->company_transaction_id,
            'usuario_registro_id' => $venta->cliente_id,
            'notas' => "Pago procesado por PagoFácil QR - Método: {$webhookData['MetodoPago']}"
        ]);

        // Vincular el pago con la transacción
        $transaction->update(['pago_id' => $pago->codigo_pago]);

        // Si es una venta a crédito, actualizar cuenta por cobrar y cuotas
        if ($venta->tipo_pago === 'credito') {
            $this->actualizarCuentaPorCobrar($venta, $transaction);
        }

        Log::info('Payment processed successfully', [
            'transaction_id' => $transaction->id,
            'pago_id' => $pago->codigo_pago,
            'venta_id' => $venta->codigo_venta
        ]);
    }

    /**
     * Actualizar cuenta por cobrar y cuotas
     */
    private function actualizarCuentaPorCobrar($venta, PagoFacilTransaction $transaction): void
    {
        $cuentaPorCobrar = $venta->cuentaPorCobrar;

        // Actualizar montos
        $cuentaPorCobrar->monto_pagado += $transaction->amount;
        $cuentaPorCobrar->saldo_pendiente = $cuentaPorCobrar->monto_total - $cuentaPorCobrar->monto_pagado;

        // Actualizar estado
        if ($cuentaPorCobrar->saldo_pendiente <= 0) {
            $cuentaPorCobrar->estado = 'pagado';
        } else {
            $cuentaPorCobrar->estado = 'parcial';
        }

        $cuentaPorCobrar->save();

        // Si hay una cuota específica asociada, marcarla como pagada
        if ($transaction->cuota_id) {
            $cuota = $transaction->cuota;
            $cuota->update([
                'estado' => 'pagada',
                'fecha_pago' => $transaction->payment_date ?? now()
            ]);
        } else {
            // Si no hay cuota específica, aplicar el pago a cuotas pendientes
            $this->aplicarPagoACuotas($cuentaPorCobrar, $transaction->amount);
        }
    }

    /**
     * Aplicar pago a cuotas pendientes (FIFO - First In First Out)
     */
    private function aplicarPagoACuotas(CuentaPorCobrar $cuentaPorCobrar, float $montoPago): void
    {
        $cuotasPendientes = $cuentaPorCobrar->cuotas()
            ->where('estado', 'pendiente')
            ->orderBy('numero_cuota')
            ->get();

        $montoRestante = $montoPago;

        foreach ($cuotasPendientes as $cuota) {
            if ($montoRestante <= 0) {
                break;
            }

            if ($montoRestante >= $cuota->monto) {
                // Pagar cuota completa
                $cuota->update([
                    'estado' => 'pagada',
                    'fecha_pago' => now()
                ]);
                $montoRestante -= $cuota->monto;
            } else {
                // Pago parcial - crear nueva cuota con el saldo
                $cuota->update(['monto' => $cuota->monto - $montoRestante]);
                $montoRestante = 0;
            }
        }
    }

    /**
     * Respuesta de éxito para PagoFácil
     */
    private function successResponse(string $message = 'Notificación recibida')
    {
        return response()->json([
            'error' => 0,
            'status' => 1,
            'message' => $message,
            'values' => true
        ], 200);
    }

    /**
     * Respuesta de error para PagoFácil
     */
    private function errorResponse(string $message = 'Error', int $code = 500)
    {
        return response()->json([
            'error' => 1,
            'status' => 0,
            'message' => $message,
            'values' => false
        ], $code);
    }
}
