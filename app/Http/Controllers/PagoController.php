<?php

namespace App\Http\Controllers;

use App\Enum\PermissionEnum;
use App\Models\Pago;
use App\Models\Venta;
use App\Models\CuentaPorCobrar;
use App\Traits\HasRolePermissionChecks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PagoController extends Controller
{
    use HasRolePermissionChecks;

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
     * Registrar nuevo pago
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

            // Verificar que la venta sea a crédito
            if ($venta->tipo_pago !== 'credito') {
                throw new \Exception('Esta venta no es a crédito.');
            }

            $cuentaPorCobrar = $venta->cuentaPorCobrar;

            if (!$cuentaPorCobrar) {
                throw new \Exception('No se encontró cuenta por cobrar para esta venta.');
            }

            // Verificar que el monto no exceda el saldo pendiente
            if ($validated['monto'] > $cuentaPorCobrar->saldo_pendiente) {
                throw new \Exception('El monto del pago excede el saldo pendiente.');
            }

            // Registrar pago
            Pago::create([
                'venta' => $validated['venta'],
                'monto' => $validated['monto'],
                'fecha_pago' => $validated['fecha_pago'],
                'metodo_pago' => $validated['metodo_pago'],
                'referencia' => $validated['referencia'] ?? null,
                'usuario_registro' => $request->user()->id,
                'notas' => $validated['notas'] ?? null,
            ]);

            // Actualizar cuenta por cobrar
            $cuentaPorCobrar->monto_pagado += $validated['monto'];
            $cuentaPorCobrar->saldo_pendiente -= $validated['monto'];
            $cuentaPorCobrar->save();

            // Actualizar estado de la cuenta
            $cuentaPorCobrar->actualizarEstado();

            DB::commit();

            return redirect()->route('pagos.index')
                ->with('success', 'Pago registrado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al registrar el pago: ' . $e->getMessage())
                ->withInput();
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

            // Revertir montos
            $cuentaPorCobrar->monto_pagado -= $pago->monto;
            $cuentaPorCobrar->saldo_pendiente += $pago->monto;
            $cuentaPorCobrar->save();

            // Actualizar estado
            $cuentaPorCobrar->actualizarEstado();

            // Eliminar pago
            $pago->delete();

            DB::commit();

            return redirect()->route('pagos.index')
                ->with('success', 'Pago eliminado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al eliminar el pago: ' . $e->getMessage());
        }
    }
}
