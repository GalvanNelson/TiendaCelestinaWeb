<?php

namespace App\Http\Controllers;

use App\Enum\PermissionEnum;
use App\Models\Pago;
use App\Models\Venta;
use App\Models\CuentaPorCobrar;
use App\Traits\HasRolePermissionChecks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PagoController extends Controller
{
    use HasRolePermissionChecks;

    private $apiBaseUrl = 'https://masterqr.pagofacil.com.bo/api/services/v2';

    /**
     * Obtener token de autenticación de Pago Fácil
     */
    private function obtenerTokenPagoFacil()
    {
        // Intentar obtener token del cache (válido por 700 minutos según la API)
        return Cache::remember('pagofacil_token', 700 * 60, function () {
            try {
                $response = Http::withHeaders([
                    'tcTokenSecret' => config('services.pagofacil.token_secret'),
                    'tcTokenService' => config('services.pagofacil.token_service'),
                ])->post($this->apiBaseUrl . '/login');

                if (!$response->successful()) {
                    throw new \Exception('Error al autenticar con Pago Fácil');
                }

                $data = $response->json();

                if ($data['error'] !== 0) {
                    throw new \Exception($data['message'] ?? 'Error de autenticación');
                }

                return $data['values']['accessToken'];
            } catch (\Exception $e) {
                Log::error('Error al obtener token Pago Fácil', [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Mostrar lista de pagos
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $query = Pago::with(['venta.cliente', 'usuarioRegistro']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('referencia', 'like', "%{$search}%")
                  ->orWhereHas('venta', function($q) use ($search) {
                      $q->where('numero_venta', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('metodo_pago')) {
            $query->where('metodo_pago', $request->metodo_pago);
        }

        $pagos = $query->orderBy('fecha_pago', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($pago) {
                return [
                    'codigo_pago' => $pago->codigo_pago,
                    'numero_venta' => $pago->venta->numero_venta,
                    'codigo_venta' => $pago->venta->codigo_venta,
                    'cliente' => $pago->venta->cliente->name ?? 'N/A',
                    'monto' => $pago->monto,
                    'fecha_pago' => $pago->fecha_pago->format('d/m/Y H:i'),
                    'fecha_pago_raw' => $pago->fecha_pago,
                    'metodo_pago' => $pago->metodo_pago,
                    'referencia' => $pago->referencia,
                    'usuario_registro' => $pago->usuarioRegistro->name,
                    'notas' => $pago->notas,
                ];
            });

        // Obtener ventas a crédito con saldo pendiente
        $ventasCredito = Venta::with(['cliente', 'cuentaPorCobrar'])
            ->where('tipo_pago', 'credito')
            ->whereHas('cuentaPorCobrar', function($q) {
                $q->where('saldo_pendiente', '>', 0);
            })
            ->get()
            ->map(function ($venta) {
                return [
                    'codigo_venta' => $venta->codigo_venta,
                    'numero_venta' => $venta->numero_venta,
                    'cliente' => $venta->cliente->name ?? 'N/A',
                    'total' => $venta->total,
                    'saldo_pendiente' => $venta->cuentaPorCobrar->saldo_pendiente ?? 0,
                ];
            });

        return Inertia::render('Pagos/Index', [
            'pagos' => $pagos,
            'ventasCredito' => $ventasCredito,
            'filters' => $request->only(['search', 'metodo_pago']),
            'can' => [
                'create' => $user->hasPermission(PermissionEnum::CREATE_PAGOS->value),
                'delete' => $user->hasPermission(PermissionEnum::DELETE_PAGOS->value),
            ],
        ]);
    }

    /**
     * Registrar nuevo pago (efectivo, transferencia, tarjeta, cheque)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'venta' => 'required|exists:ventas,codigo_venta',
            'monto' => 'required|numeric|min:0.01',
            'fecha_pago' => 'required|date',
            'metodo_pago' => 'required|in:efectivo,transferencia,tarjeta,cheque',
            'referencia' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:1000',
        ], [
            'venta.required' => 'La venta es obligatoria.',
            'venta.exists' => 'La venta seleccionada no existe.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.min' => 'El monto debe ser mayor a 0.',
            'metodo_pago.required' => 'El método de pago es obligatorio.',
            'fecha_pago.required' => 'La fecha de pago es obligatoria.',
        ]);

        DB::beginTransaction();
        try {
            $venta = Venta::findOrFail($validated['venta']);

            if ($venta->tipo_pago !== 'credito') {
                throw new \Exception('Esta venta no es a crédito.');
            }

            $cuentaPorCobrar = $venta->cuentaPorCobrar;

            if (!$cuentaPorCobrar) {
                throw new \Exception('No se encontró cuenta por cobrar para esta venta.');
            }

            if ($validated['monto'] > $cuentaPorCobrar->saldo_pendiente) {
                throw new \Exception('El monto del pago excede el saldo pendiente.');
            }

            Pago::create([
                'venta' => $validated['venta'],
                'monto' => $validated['monto'],
                'fecha_pago' => $validated['fecha_pago'],
                'metodo_pago' => $validated['metodo_pago'],
                'referencia' => $validated['referencia'] ?? 'PAGO-' . time(),
                'usuario_registro' => $request->user()->id,
                'notas' => $validated['notas'] ?? null,
            ]);

            $cuentaPorCobrar->monto_pagado += $validated['monto'];
            $cuentaPorCobrar->saldo_pendiente -= $validated['monto'];
            $cuentaPorCobrar->save();
            $cuentaPorCobrar->actualizarEstado();

            DB::commit();

            return redirect()->route('pagos.index')
                ->with('success', 'Pago registrado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar pago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Error al registrar el pago: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Listar métodos de pago habilitados en Pago Fácil
     */
    public function listarMetodosHabilitados()
    {
        try {
            $token = $this->obtenerTokenPagoFacil();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Response-Language' => 'es'
            ])->post($this->apiBaseUrl . '/list-enabled-services');

            if (!$response->successful()) {
                throw new \Exception('Error al consultar métodos habilitados');
            }

            $data = $response->json();

            if ($data['error'] !== 0) {
                throw new \Exception($data['message'] ?? 'Error al listar métodos');
            }

            return response()->json([
                'success' => true,
                'data' => $data['values']
            ]);

        } catch (\Exception $e) {
            Log::error('Error al listar métodos habilitados', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al consultar métodos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar pago con QR - según documentación oficial v2
     */
    public function pagarConQR(Request $request)
    {
        try {
            // Log inicial
            Log::info('=== INICIO: Generar QR ===', [
                'user_id' => $request->user()?->id,
                'request_data' => $request->all()
            ]);

            // Validación
            $validated = $request->validate([
                'venta_id' => 'required|exists:ventas,codigo_venta',
                'monto' => 'required|numeric|min:0.1',
                'cliente_name' => 'required|string|max:255',
                'document_type' => 'required|integer|in:1,2,3,4,5',
                'document_id' => 'required|string|max:50',
                'phone_number' => 'required|string|max:20',
                'email' => 'required|email|max:255',
            ]);

            Log::info('Validación exitosa', ['validated' => $validated]);

            DB::beginTransaction();

            // Verificar venta
            $venta = Venta::with('detalles.producto')->find($validated['venta_id']);

            if (!$venta) {
                throw new \Exception('Venta no encontrada');
            }

            Log::info('Venta encontrada', [
                'venta_id' => $venta->codigo_venta,
                'tipo_pago' => $venta->tipo_pago
            ]);

            if ($venta->tipo_pago !== 'credito') {
                throw new \Exception('Esta venta no es a crédito.');
            }

            $cuentaPorCobrar = $venta->cuentaPorCobrar;

            if (!$cuentaPorCobrar) {
                throw new \Exception('No se encontró cuenta por cobrar para esta venta.');
            }

            if ($validated['monto'] > $cuentaPorCobrar->saldo_pendiente) {
                throw new \Exception('El monto del pago excede el saldo pendiente.');
            }

            // Obtener token de autenticación
            Log::info('Obteniendo token de Pago Fácil...');
            $token = $this->obtenerTokenPagoFacil();
            Log::info('Token obtenido exitosamente');

            // Generar número de pago único
            $paymentNumber = config('services.pagofacil.client_code', 'GRUPO14SA') .
                           '-' . $venta->numero_venta .
                           '-' . time();

            Log::info('Payment number generado', ['paymentNumber' => $paymentNumber]);

            // Preparar detalle de la orden
            $orderDetail = [];
            $serial = 1;

            if ($venta->detalles && $venta->detalles->count() > 0) {
                foreach ($venta->detalles as $detalle) {
                    $orderDetail[] = [
                        "serial" => $serial++,
                        "product" => $detalle->producto->nombre ?? 'Producto',
                        "quantity" => (int)$detalle->cantidad,
                        "price" => floatval($detalle->precio_unitario),
                        "discount" => floatval($detalle->descuento ?? 0),
                        "total" => floatval($detalle->subtotal)
                    ];
                }
            } else {
                // Si no hay detalles, crear uno genérico
                $orderDetail[] = [
                    "serial" => 1,
                    "product" => "Pago de venta " . $venta->numero_venta,
                    "quantity" => 1,
                    "price" => floatval($validated['monto']),
                    "discount" => 0,
                    "total" => floatval($validated['monto'])
                ];
            }

            // Preparar payload
            $payload = [
                'paymentMethod' => (int)config('services.pagofacil.payment_method_id', 4),
                'clientName' => $validated['cliente_name'],
                'documentType' => (int)$validated['document_type'],
                'documentId' => $validated['document_id'],
                'phoneNumber' => $validated['phone_number'],
                'email' => $validated['email'],
                'paymentNumber' => $paymentNumber,
                'amount' => floatval($validated['monto']),
                'currency' => 2,
                'clientCode' => config('services.pagofacil.client_code', 'GRUPO14SA'),
                'callbackUrl' => config('services.pagofacil.callback_url', url('/api/pagos/callback')),
                'orderDetail' => $orderDetail
            ];

            Log::info('Payload preparado', ['payload' => $payload]);

            // Llamar a la API de Pago Fácil
            Log::info('Llamando a API de Pago Fácil...');
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Response-Language' => 'es'
            ])->timeout(30)->post($this->apiBaseUrl . '/generate-qr', $payload);

            Log::info('Respuesta recibida de Pago Fácil', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if (!$response->successful()) {
                throw new \Exception('Error en API Pago Fácil: ' . ($response->json()['message'] ?? 'Error desconocido'));
            }

            $qrData = $response->json();

            if ($qrData['error'] !== 0) {
                throw new \Exception($qrData['message'] ?? 'Error al generar QR');
            }

            // Extraer datos
            $values = $qrData['values'];
            $transactionId = $values['transactionId'] ?? null;
            $qrBase64 = $values['qrBase64'] ?? null;

            if (!$transactionId || !$qrBase64) {
                throw new \Exception('Respuesta incompleta de Pago Fácil');
            }

            // Construir imagen base64 completa
            $qrImage = 'data:image/png;base64,' . $qrBase64;

            // Registrar el pago
            Log::info('Registrando pago en base de datos...');
            $pago = Pago::create([
                'venta' => $validated['venta_id'],
                'monto' => $validated['monto'],
                'fecha_pago' => now(),
                'metodo_pago' => 'qr',
                'referencia' => $paymentNumber,
                'usuario_registro' => $request->user()->id,
                'notas' => "Pago QR generado\n" .
                          "ID Transacción PagoFácil: {$transactionId}\n" .
                          "ID Transacción Empresa: {$paymentNumber}\n" .
                          "Estado: Pendiente de confirmación\n" .
                          "Fecha expiración: " . ($values['expirationDate'] ?? 'N/A')
            ]);

            Log::info('Pago registrado', ['pago_id' => $pago->codigo_pago]);

            DB::commit();

            Log::info('=== FIN: QR generado exitosamente ===');

            return response()->json([
                'success' => true,
                'message' => 'QR generado exitosamente',
                'data' => [
                    'pago_id' => $pago->codigo_pago,
                    'qrImage' => $qrImage,
                    'transactionId' => $transactionId,
                    'paymentNumber' => $paymentNumber,
                    'monto' => $validated['monto'],
                    'expirationDate' => $values['expirationDate'] ?? null,
                    'checkoutUrl' => $values['checkoutUrl'] ?? null,
                    'deepLink' => $values['deepLink'] ?? null,
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación', [
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('=== ERROR al generar QR ===', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar estado del pago QR - según documentación oficial
     */
    public function verificarPago(Request $request)
    {
        $validated = $request->validate([
            'transactionId' => 'nullable|string',
            'paymentNumber' => 'nullable|string',
        ]);

        // Al menos uno debe estar presente
        if (!$validated['transactionId'] && !$validated['paymentNumber']) {
            return response()->json([
                'success' => false,
                'message' => 'Debe proporcionar transactionId o paymentNumber'
            ], 422);
        }

        try {
            Log::info('Verificando estado del pago', $validated);

            $token = $this->obtenerTokenPagoFacil();

            // Preparar body según documentación
            $body = [];
            if (!empty($validated['transactionId'])) {
                $body['pagofacilTransactionId'] = (int)$validated['transactionId'];
            }
            if (!empty($validated['paymentNumber'])) {
                $body['companyTransactionId'] = $validated['paymentNumber'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Response-Language' => 'es'
            ])->timeout(30)->post($this->apiBaseUrl . '/query-transaction', $body);

            if (!$response->successful()) {
                throw new \Exception('Error al consultar el estado del pago');
            }

            $data = $response->json();
            Log::info('Respuesta de verificación', ['data' => $data]);

            if ($data['error'] !== 0) {
                throw new \Exception($data['message'] ?? 'Error al verificar pago');
            }

            $values = $data['values'];

            // Estados según documentación:
            // paymentStatus: 1 = Pendiente, 2 = Completado, 3 = Rechazado, etc.
            $estadoTexto = match((int)$values['paymentStatus']) {
                1 => 'pendiente',
                2 => 'completado',
                3 => 'rechazado',
                4 => 'cancelado',
                default => 'desconocido'
            };

            return response()->json([
                'success' => true,
                'estado' => $estadoTexto,
                'data' => $values
            ]);

        } catch (\Exception $e) {
            Log::error('Error al verificar pago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al verificar el pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmar pago QR después de verificación exitosa
     */
    public function confirmarPagoQR(Request $request)
    {
        $validated = $request->validate([
            'pago_id' => 'required|exists:pagos,codigo_pago',
            'transactionId' => 'required|string'
        ]);

        DB::beginTransaction();
        try {
            $pago = Pago::findOrFail($validated['pago_id']);

            if ($pago->metodo_pago !== 'qr') {
                throw new \Exception('Este no es un pago QR');
            }

            $venta = $pago->venta;
            $cuentaPorCobrar = $venta->cuentaPorCobrar;

            if (!$cuentaPorCobrar) {
                throw new \Exception('No se encontró cuenta por cobrar');
            }

            // Actualizar cuenta por cobrar
            $cuentaPorCobrar->monto_pagado += $pago->monto;
            $cuentaPorCobrar->saldo_pendiente -= $pago->monto;
            $cuentaPorCobrar->save();
            $cuentaPorCobrar->actualizarEstado();

            // Actualizar notas del pago
            $pago->notas = $pago->notas . "\n\n✅ PAGO CONFIRMADO\n" .
                          "Fecha confirmación: " . now()->format('Y-m-d H:i:s') . "\n" .
                          "ID Transacción: {$validated['transactionId']}";
            $pago->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago confirmado exitosamente',
                'data' => $pago
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al confirmar pago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar el pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Callback de Pago Fácil (webhook) - según documentación oficial
     */
    public function callbackPagoQR(Request $request)
    {
        try {
            Log::info('Callback recibido de Pago Fácil', ['data' => $request->all()]);

            // Estructura según documentación:
            // PedidoID, Fecha, Hora, MetodoPago, Estado
            $pedidoID = $request->input('PedidoID');
            $fecha = $request->input('Fecha');
            $hora = $request->input('Hora');
            $metodoPago = $request->input('MetodoPago');
            $estado = $request->input('Estado');

            if (!$pedidoID) {
                throw new \Exception('No se recibió el PedidoID');
            }

            // Buscar el pago por la referencia (paymentNumber)
            $pago = Pago::where('referencia', $pedidoID)
                        ->where('metodo_pago', 'qr')
                        ->first();

            if (!$pago) {
                Log::warning('Pago no encontrado para callback', ['PedidoID' => $pedidoID]);
                throw new \Exception('Pago no encontrado');
            }

            DB::beginTransaction();

            // Actualizar notas con información del callback
            $pago->notas = $pago->notas . "\n\n📥 CALLBACK RECIBIDO\n" .
                          "Fecha: {$fecha} {$hora}\n" .
                          "Método Pago: {$metodoPago}\n" .
                          "Estado: {$estado}";

            // Si el pago fue exitoso, actualizar la cuenta por cobrar
            if (strtolower($estado) === 'pagado' || strtolower($estado) === 'completado') {
                $cuentaPorCobrar = $pago->venta->cuentaPorCobrar;

                if ($cuentaPorCobrar) {
                    $cuentaPorCobrar->monto_pagado += $pago->monto;
                    $cuentaPorCobrar->saldo_pendiente -= $pago->monto;
                    $cuentaPorCobrar->save();
                    $cuentaPorCobrar->actualizarEstado();
                }

                $pago->notas .= "\n✅ Pago confirmado automáticamente vía callback";
            } else {
                $pago->notas .= "\n❌ Pago no completado - Estado: {$estado}";
            }

            $pago->save();
            DB::commit();

            // Responder según documentación oficial
            return response()->json([
                'error' => 0,
                'status' => 1,
                'message' => 'Callback procesado correctamente',
                'values' => true
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 1,
                'status' => 0,
                'message' => 'Error al procesar callback: ' . $e->getMessage(),
                'values' => false
            ], 500);
        }
    }

    /**
     * Eliminar pago
     */
    public function destroy(Pago $pago)
    {
        DB::beginTransaction();
        try {
            $cuentaPorCobrar = $pago->venta->cuentaPorCobrar;

            // Solo revertir si el pago no es QR o si ya fue confirmado
            if ($cuentaPorCobrar && $pago->metodo_pago !== 'qr') {
                $cuentaPorCobrar->monto_pagado -= $pago->monto;
                $cuentaPorCobrar->saldo_pendiente += $pago->monto;
                $cuentaPorCobrar->save();
                $cuentaPorCobrar->actualizarEstado();
            }

            $pago->delete();
            DB::commit();

            return redirect()->route('pagos.index')
                ->with('success', 'Pago eliminado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar pago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Error al eliminar el pago: ' . $e->getMessage());
        }
    }
}
