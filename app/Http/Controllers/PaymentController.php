<?php

namespace App\Http\Controllers;

use App\Models\CuentaPorCobrar;
use App\Models\Cuota;
use App\Models\Pago;
use App\Models\PagoFacilTransaction;
use App\Models\Venta;
use App\Services\PagoFacilService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private PagoFacilService $pagoFacilService;

    public function __construct(PagoFacilService $pagoFacilService)
    {
        $this->pagoFacilService = $pagoFacilService;
    }

    /**
     * Listar métodos de pago disponibles
     */
    public function getPaymentMethods()
    {
        try {
            $methods = $this->pagoFacilService->listEnabledServices();

            return response()->json([
                'success' => true,
                'methods' => $methods
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener métodos de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar QR para pago de venta (contado o primera cuota de crédito)
     */
    public function generatePaymentQR(Request $request, Venta $venta)
    {
        $validated = $request->validate([
            'payment_method_id' => 'required|integer',
            'phone_number' => 'nullable|string',
            'email' => 'nullable|email',
            'monto' => 'nullable|numeric|min:0.1', // Para pagos a crédito parciales
        ]);

        DB::beginTransaction();
        try {
            $cliente = $venta->cliente;

            // Determinar el monto
            if ($venta->tipo_pago === 'credito') {
                // Para crédito, puede ser un monto parcial o el total
                $monto = $validated['monto'] ?? $venta->total;

                // Validar que no exceda el saldo pendiente
                $cuentaPorCobrar = $venta->cuentaPorCobrar;
                if ($monto > $cuentaPorCobrar->saldo_pendiente) {
                    throw new \Exception('El monto excede el saldo pendiente');
                }
            } else {
                // Para contado, es el total
                $monto = $venta->total;
            }

            // Generar ID único de transacción
            $companyTransactionId = $venta->numero_venta . '-' . now()->timestamp;

            // Preparar detalles del pedido
            $orderDetail = $venta->detalles->map(function ($detalle, $index) {
                return [
                    'serial' => $index + 1,
                    'product' => $detalle->producto->nombre ?? 'Producto',
                    'quantity' => $detalle->cantidad,
                    'price' => $detalle->precio_unitario,
                    'discount' => 0,
                    'total' => $detalle->subtotal
                ];
            })->toArray();

            // Parámetros para PagoFácil
            $params = [
                'paymentMethod' => $validated['payment_method_id'],
                'clientName' => $cliente->name . ' ' . ($cliente->apellido ?? ''),
                'documentType' => config('pagofacil.payment.document_type'),
                'documentId' => $cliente->ci ?? $cliente->id,
                'phoneNumber' => $validated['phone_number'] ?? $cliente->telefono ?? '',
                'email' => $validated['email'] ?? $cliente->email ?? '',
                'paymentNumber' => $companyTransactionId,
                'amount' => $monto,
                'currency' => config('pagofacil.payment.currency'),
                'clientCode' => $cliente->id,
                'callbackUrl' => config('pagofacil.webhook.url'),
                'orderDetail' => $orderDetail
            ];

            // Generar QR en PagoFácil
            $qrData = $this->pagoFacilService->generateQR($params);

            // Guardar transacción en BD
            $transaction = PagoFacilTransaction::create([
                'venta_id' => $venta->codigo_venta,
                'pagofacil_transaction_id' => $qrData['transactionId'] ?? null,
                'company_transaction_id' => $companyTransactionId,
                'payment_method_id' => $validated['payment_method_id'],
                'client_name' => $params['clientName'],
                'document_id' => $params['documentId'],
                'phone_number' => $params['phoneNumber'],
                'email' => $params['email'],
                'amount' => $monto,
                'currency' => $params['currency'],
                'qr_base64' => $qrData['qrBase64'] ?? null,
                'checkout_url' => $qrData['checkoutUrl'] ?? null,
                'deep_link' => $qrData['deepLink'] ?? null,
                'qr_content_url' => $qrData['qrContentUrl'] ?? null,
                'universal_url' => $qrData['universalUrl'] ?? null,
                'expiration_date' => $qrData['expirationDate'] ?? null,
                'status' => 'pending',
                'order_detail' => $orderDetail,
                'callback_url' => $params['callbackUrl'],
                'response_data' => $qrData,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'transaction' => [
                    'id' => $transaction->id,
                    'company_transaction_id' => $transaction->company_transaction_id,
                    'qr_base64' => $transaction->qr_base64,
                    'checkout_url' => $transaction->checkout_url,
                    'deep_link' => $transaction->deep_link,
                    'universal_url' => $transaction->universal_url,
                    'amount' => $transaction->amount,
                    'expiration_date' => $transaction->expiration_date,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error generando QR de pago', [
                'venta_id' => $venta->codigo_venta,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar código QR: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar QR para pago de una cuota específica
     */
    public function generateCuotaPaymentQR(Request $request, Cuota $cuota)
    {
        $validated = $request->validate([
            'payment_method_id' => 'required|integer',
            'phone_number' => 'nullable|string',
            'email' => 'nullable|email',
            'monto' => 'nullable|numeric|min:0.1',
        ]);

        DB::beginTransaction();
        try {
            $cuentaPorCobrar = $cuota->cuentaPorCobrar;
            $venta = $cuentaPorCobrar->venta;
            $cliente = $venta->cliente;

            // El monto puede ser parcial o total de la cuota
            $monto = $validated['monto'] ?? $cuota->monto;

            if ($cuota->estado === 'pagada') {
                throw new \Exception('Esta cuota ya ha sido pagada');
            }

            // Generar ID único
            $companyTransactionId = "CUOTA-{$cuota->codigo_cuota}-" . now()->timestamp;

            // Preparar detalles
            $orderDetail = [
                [
                    'serial' => 1,
                    'product' => "Cuota #{$cuota->numero_cuota} - Venta {$venta->numero_venta}",
                    'quantity' => 1,
                    'price' => $monto,
                    'discount' => 0,
                    'total' => $monto
                ]
            ];

            $params = [
                'paymentMethod' => $validated['payment_method_id'],
                'clientName' => $cliente->name . ' ' . ($cliente->apellido ?? ''),
                'documentType' => config('pagofacil.payment.document_type'),
                'documentId' => $cliente->ci ?? $cliente->id,
                'phoneNumber' => $validated['phone_number'] ?? $cliente->telefono ?? '',
                'email' => $validated['email'] ?? $cliente->email ?? '',
                'paymentNumber' => $companyTransactionId,
                'amount' => $monto,
                'currency' => config('pagofacil.payment.currency'),
                'clientCode' => $cliente->id,
                'callbackUrl' => config('pagofacil.webhook.url'),
                'orderDetail' => $orderDetail
            ];

            $qrData = $this->pagoFacilService->generateQR($params);

            $transaction = PagoFacilTransaction::create([
                'venta_id' => $venta->codigo_venta,
                'cuota_id' => $cuota->codigo_cuota,
                'pagofacil_transaction_id' => $qrData['transactionId'] ?? null,
                'company_transaction_id' => $companyTransactionId,
                'payment_method_id' => $validated['payment_method_id'],
                'client_name' => $params['clientName'],
                'document_id' => $params['documentId'],
                'phone_number' => $params['phoneNumber'],
                'email' => $params['email'],
                'amount' => $monto,
                'currency' => $params['currency'],
                'qr_base64' => $qrData['qrBase64'] ?? null,
                'checkout_url' => $qrData['checkoutUrl'] ?? null,
                'deep_link' => $qrData['deepLink'] ?? null,
                'qr_content_url' => $qrData['qrContentUrl'] ?? null,
                'universal_url' => $qrData['universalUrl'] ?? null,
                'expiration_date' => $qrData['expirationDate'] ?? null,
                'status' => 'pending',
                'order_detail' => $orderDetail,
                'callback_url' => $params['callbackUrl'],
                'response_data' => $qrData,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'transaction' => [
                    'id' => $transaction->id,
                    'company_transaction_id' => $transaction->company_transaction_id,
                    'qr_base64' => $transaction->qr_base64,
                    'checkout_url' => $transaction->checkout_url,
                    'deep_link' => $transaction->deep_link,
                    'universal_url' => $transaction->universal_url,
                    'amount' => $transaction->amount,
                    'expiration_date' => $transaction->expiration_date,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error generando QR para cuota', [
                'cuota_id' => $cuota->codigo_cuota,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar código QR: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consultar estado de transacción
     */
    public function checkTransactionStatus(PagoFacilTransaction $transaction)
    {
        try {
            $status = $this->pagoFacilService->queryTransaction(
                $transaction->company_transaction_id,
                false
            );

            // Actualizar transacción si cambió el estado
            if (isset($status['paymentStatus']) && $status['paymentStatus'] !== $transaction->status) {
                $this->updateTransactionStatus($transaction, $status);
            }

            return response()->json([
                'success' => true,
                'status' => $status,
                'transaction' => $transaction->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar estado de transacción
     */
    private function updateTransactionStatus(PagoFacilTransaction $transaction, array $statusData): void
    {
        DB::beginTransaction();
        try {
            // Mapear estados de PagoFácil a nuestros estados
            $newStatus = $this->mapPagoFacilStatus($statusData['paymentStatus']);

            $transaction->update([
                'status' => $newStatus,
                'payment_date' => $statusData['paymentDate'] ?? null,
                'payment_time' => $statusData['paymentTime'] ?? null,
                'response_data' => array_merge($transaction->response_data ?? [], $statusData)
            ]);

            // Si el pago fue completado, procesar el pago
            if ($newStatus === 'completed') {
                $this->procesarPagoExitoso($transaction);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando estado de transacción', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Procesar pago exitoso
     */
    private function procesarPagoExitoso(PagoFacilTransaction $transaction): void
    {
        $venta = $transaction->venta;

        // Crear registro de pago
        $pago = Pago::create([
            'venta_id' => $venta->codigo_venta,
            'monto' => $transaction->amount,
            'fecha_pago' => $transaction->payment_date ?? now(),
            'metodo_pago' => 'qr',
            'referencia' => $transaction->pagofacil_transaction_id,
            'usuario_registro_id' => $venta->cliente_id,
            'notas' => "Pago procesado por PagoFácil - Trans: {$transaction->company_transaction_id}"
        ]);

        // Vincular el pago con la transacción
        $transaction->update(['pago_id' => $pago->codigo_pago]);

        // Si es una venta a crédito, actualizar cuenta por cobrar
        if ($venta->tipo_pago === 'credito') {
            $cuentaPorCobrar = $venta->cuentaPorCobrar;
            $cuentaPorCobrar->monto_pagado += $transaction->amount;
            $cuentaPorCobrar->saldo_pendiente = $cuentaPorCobrar->monto_total - $cuentaPorCobrar->monto_pagado;

            // Actualizar estado
            if ($cuentaPorCobrar->saldo_pendiente <= 0) {
                $cuentaPorCobrar->estado = 'pagado';
            } else {
                $cuentaPorCobrar->estado = 'parcial';
            }

            $cuentaPorCobrar->save();

            // Si hay una cuota asociada, marcarla como pagada
            if ($transaction->cuota_id) {
                $cuota = $transaction->cuota;
                $cuota->update([
                    'estado' => 'pagada',
                    'fecha_pago' => $transaction->payment_date ?? now()
                ]);
            }
        }
    }

    /**
     * Mapear estados de PagoFácil a nuestros estados
     */
    private function mapPagoFacilStatus(int $pagoFacilStatus): string
    {
        return match($pagoFacilStatus) {
            1 => 'pending',
            2 => 'completed',
            3 => 'expired',
            4 => 'failed',
            default => 'pending'
        };
    }
}
