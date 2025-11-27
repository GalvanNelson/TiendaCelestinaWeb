<?php

namespace App\Http\Controllers;

use App\Enum\PermissionEnum;
use App\Enum\RoleEnum;
use App\Models\CuentaPorCobrar;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\SalidaStock;
use App\Models\Venta;
use App\Models\User;
use App\Traits\HasRolePermissionChecks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class VentaController extends Controller
{
    use HasRolePermissionChecks;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $query = Venta::with(['cliente', 'vendedor']);

        // Filtro
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('numero_venta', 'like', "%{$search}%")
                    ->orWhereHas('cliente', function ($q2) use ($search) {
                        $q2->where('name', 'ilike', "%{$search}%");
                    });
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_pago')) {
            $query->where('tipo_pago', $request->tipo_pago);
        }

        $ventas = $query->orderBy('fecha_venta', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($venta) {
                return [
                    'codigo_venta' => $venta->codigo_venta,
                    'numero_venta' => $venta->numero_venta,
                    'cliente' => $venta->cliente?->name,
                    'vendedor' => $venta->vendedor?->name,
                    'fecha_venta' => $venta->fecha_venta->toDateString(),
                    'tipo_pago' => $venta->tipo_pago,
                    'subtotal' => $venta->subtotal,
                    'descuento' => $venta->descuento,
                    'total' => $venta->total,
                    'estado' => $venta->estado,
                    'notas' => $venta->notas,
                ];
            });

        // Obtener clientes
        $clienteRole = \App\Models\Role::where('name', RoleEnum::CLIENTE->value)->first();
        $clientes = User::whereHas('roles', function ($query) use ($clienteRole) {
            $query->where('role_id', $clienteRole->id);
        })->orderBy('name')->get()->map(function ($cliente) {
            return [
                'id' => $cliente->id,
                'nombre' => $cliente->name . ' ' . ($cliente->apellido ?? ''),
            ];
        });

        // Obtener productos con stock
        $productos = Producto::with('unidadMedida')
            ->where('stock', '>', 0)
            ->orderBy('nombre')
            ->get()
            ->map(function ($prod) {
                return [
                    'codigo' => $prod->codigo_producto,
                    'nombre' => $prod->nombre,
                    'precio' => $prod->precio_unitario,
                    'stock' => $prod->stock,
                    'unidad' => $prod->unidadMedida?->nombre,
                ];
            });

        return Inertia::render('Ventas/Index', [
            'ventas' => $ventas,
            'clientes' => $clientes,
            'productos' => $productos,
            'filters' => $request->only(['search', 'estado', 'tipo_pago']),
            'can' => [
                'create' => $user->hasPermission(PermissionEnum::CREATE_SALES->value),
                'edit' => $user->hasPermission(PermissionEnum::EDIT_SALES->value),
                'delete' => $user->hasPermission(PermissionEnum::DELETE_SALES->value),
            ],
        ]);
    }

/**
     * Guardar nueva venta
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:users,id',
            'fecha_venta' => 'required|date',
            'tipo_pago' => 'required|in:contado,credito',
            'descuento' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:1000',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto' => 'required|exists:productos,codigo_producto',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'fecha_vencimiento' => 'required_if:tipo_pago,credito|nullable|date|after:fecha_venta',
        ], [
            'cliente_id.required' => 'El cliente es obligatorio.',
            'detalles.required' => 'Debe agregar al menos un producto.',
            'detalles.min' => 'Debe agregar al menos un producto.',
            'fecha_vencimiento.required_if' => 'La fecha de vencimiento es obligatoria para ventas a crédito.',
        ]);

        DB::beginTransaction();
        try {
            // Calcular totales
            $subtotal = 0;
            foreach ($validated['detalles'] as $detalle) {
                $subtotal += $detalle['cantidad'] * $detalle['precio_unitario'];
            }

            $descuento = $validated['descuento'] ?? 0;
            $total = $subtotal - $descuento;

            // Crear venta
            $venta = Venta::create([
                'cliente_id' => $validated['cliente_id'],
                'vendedor_id' => $request->user()->id,
                'fecha_venta' => $validated['fecha_venta'],
                'tipo_pago' => $validated['tipo_pago'],
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'total' => $total,
                'estado' => 'completada',
                'notas' => $validated['notas'] ?? null,
            ]);

            // Refrescar para obtener el codigo_venta generado
            $venta->refresh();

            // Crear detalles y actualizar stock
            foreach ($validated['detalles'] as $detalle) {
                $producto = Producto::where('codigo_producto', $detalle['producto'])->firstOrFail();

                // Verificar stock
                if ($producto->stock < $detalle['cantidad']) {
                    throw new \Exception("Stock insuficiente para el producto: {$producto->nombre}");
                }

                $subtotalDetalle = $detalle['cantidad'] * $detalle['precio_unitario'];

                // ✅ Crear detalle de venta
                DetalleVenta::create([
                    'venta_id' => $venta->codigo_venta,
                    'producto_id' => $detalle['producto'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'subtotal' => $subtotalDetalle,
                ]);

                // Reducir stock
                $producto->decrement('stock', $detalle['cantidad']);

                // Registrar salida de stock
                SalidaStock::create([
                    'codigo_producto' => $detalle['producto'],
                    'cantidad' => $detalle['cantidad'],
                    'motivo' => 'Venta ' . $venta->numero_venta,
                    'usuario' => $request->user()->name,
                    'fecha' => $validated['fecha_venta'],
                ]);
            }

            // Si es a crédito, crear cuenta por cobrar
            if ($validated['tipo_pago'] === 'credito') {
                $fechaVencimiento = $validated['fecha_vencimiento'] ?? now()->addDays(30);

                // ✅ Crear cuenta por cobrar - ahora usa venta_id
                CuentaPorCobrar::create([
                    'venta_id' => $venta->codigo_venta,
                    'monto_total' => $total,
                    'monto_pagado' => 0,
                    'saldo_pendiente' => $total,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'estado' => 'pendiente',
                ]);
            }

            DB::commit();

            return redirect()->route('ventas.index')
                ->with('success', 'Venta registrada exitosamente. Número: ' . $venta->numero_venta);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear venta:', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return redirect()->back()
                ->with('error', 'Error al registrar la venta: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mostrar detalles de una venta
     */
    public function show(Venta $venta): InertiaResponse
    {
        $venta->load(['cliente', 'vendedor', 'detalles.producto', 'cuentaPorCobrar', 'pagos.usuarioRegistro']);

        return Inertia::render('Ventas/Show', [
            'venta' => [
                'codigo_venta' => $venta->codigo_venta,
                'numero_venta' => $venta->numero_venta,
                'cliente' => $venta->cliente->name ?? 'N/A',
                'vendedor' => $venta->vendedor->name ?? 'N/A',
                'fecha_venta' => $venta->fecha_venta->format('d/m/Y H:i'),
                'tipo_pago' => $venta->tipo_pago,
                'subtotal' => $venta->subtotal,
                'descuento' => $venta->descuento,
                'total' => $venta->total,
                'estado' => $venta->estado,
                'notas' => $venta->notas,
                'detalles' => $venta->detalles->map(function ($detalle) {
                    return [
                        'producto' => $detalle->producto->nombre,
                        'cantidad' => $detalle->cantidad,
                        'precio_unitario' => $detalle->precio_unitario,
                        'subtotal' => $detalle->subtotal,
                    ];
                }),
                'cuenta_por_cobrar' => $venta->cuentaPorCobrar ? [
                    'monto_total' => $venta->cuentaPorCobrar->monto_total,
                    'monto_pagado' => $venta->cuentaPorCobrar->monto_pagado,
                    'saldo_pendiente' => $venta->cuentaPorCobrar->saldo_pendiente,
                    'fecha_vencimiento' => $venta->cuentaPorCobrar->fecha_vencimiento?->format('d/m/Y'),
                    'estado' => $venta->cuentaPorCobrar->estado,
                ] : null,
                'pagos' => $venta->pagos->map(function ($pago) {
                    return [
                        'codigo_pago' => $pago->codigo_pago,
                        'monto' => $pago->monto,
                        'fecha_pago' => $pago->fecha_pago->format('d/m/Y H:i'),
                        'metodo_pago' => $pago->metodo_pago,
                        'referencia' => $pago->referencia,
                        'usuario' => $pago->usuarioRegistro->name,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Cancelar venta
     */
    public function destroy(Venta $venta)
    {
        if ($venta->estado === 'cancelada') {
            return redirect()->back()->with('error', 'Esta venta ya está cancelada.');
        }

        DB::beginTransaction();
        try {
            // Revertir stock
            foreach ($venta->detalles as $detalle) {
                $producto = Producto::where('codigo_producto', $detalle->producto)->first();
                $producto->increment('stock', $detalle->cantidad);
            }

            // Cancelar venta
            $venta->update(['estado' => 'cancelada']);

            DB::commit();

            return redirect()->route('ventas.index')
                ->with('success', 'Venta cancelada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al cancelar la venta: ' . $e->getMessage());
        }
    }
}
