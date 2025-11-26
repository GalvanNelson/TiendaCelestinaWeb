<?php

namespace App\Http\Controllers;

use App\Enum\PermissionEnum;
use App\Models\CuentaPorCobrar;
use App\Models\Cuota;
use App\Traits\HasRolePermissionChecks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CuentaPorCobrarController extends Controller
{
    use HasRolePermissionChecks;

    /**
     * Mostrar lista de cuentas por cobrar
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $query = CuentaPorCobrar::with(['venta.cliente']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('venta', function($q) use ($search) {
                $q->where('numero_venta', 'like', "%{$search}%")
                  ->orWhereHas('cliente', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $cuentas = $query->orderBy('fecha_vencimiento', 'asc')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($cuenta) {
                $diasVencimiento = $cuenta->fecha_vencimiento
                    ? now()->diffInDays($cuenta->fecha_vencimiento, false)
                    : null;

                return [
                    'codigo_cuenta' => $cuenta->codigo_cuenta,
                    'numero_venta' => $cuenta->venta->numero_venta,
                    'codigo_venta' => $cuenta->venta->codigo_venta,
                    'cliente' => $cuenta->venta->cliente->name ?? 'N/A',
                    'cliente_id' => $cuenta->venta->cliente_id,
                    'monto_total' => $cuenta->monto_total,
                    'monto_pagado' => $cuenta->monto_pagado,
                    'saldo_pendiente' => $cuenta->saldo_pendiente,
                    'fecha_vencimiento' => $cuenta->fecha_vencimiento?->format('d/m/Y'),
                    'fecha_vencimiento_raw' => $cuenta->fecha_vencimiento,
                    'dias_vencimiento' => $diasVencimiento,
                    'estado' => $cuenta->estado,
                ];
            });

        // Estadísticas
        $estadisticas = [
            'total_cuentas' => CuentaPorCobrar::count(),
            'total_pendiente' => CuentaPorCobrar::sum('saldo_pendiente'),
            'cuentas_vencidas' => CuentaPorCobrar::where('estado', 'vencido')->count(),
            'monto_vencido' => CuentaPorCobrar::where('estado', 'vencido')->sum('saldo_pendiente'),
        ];

        return Inertia::render('CuentasPorCobrar/Index', [
            'cuentas' => $cuentas,
            'estadisticas' => $estadisticas,
            'filters' => $request->only(['search', 'estado']),
            'can' => [
                'view' => $user->hasPermission(PermissionEnum::VIEW_CUENTAS_COBRAR->value),
            ],
        ]);
    }

    /**
     * Mostrar detalles de una cuenta por cobrar
     */
    public function show(CuentaPorCobrar $cuentaPorCobrar): InertiaResponse
    {
        $cuentaPorCobrar->load(['venta.cliente', 'venta.pagos.usuarioRegistro', 'cuotas']);

        return Inertia::render('CuentasPorCobrar/Show', [
            'cuenta' => [
                'codigo_cuenta' => $cuentaPorCobrar->codigo_cuenta,
                'numero_venta' => $cuentaPorCobrar->venta->numero_venta,
                'cliente' => $cuentaPorCobrar->venta->cliente->name ?? 'N/A',
                'monto_total' => $cuentaPorCobrar->monto_total,
                'monto_pagado' => $cuentaPorCobrar->monto_pagado,
                'saldo_pendiente' => $cuentaPorCobrar->saldo_pendiente,
                'fecha_vencimiento' => $cuentaPorCobrar->fecha_vencimiento?->format('d/m/Y'),
                'estado' => $cuentaPorCobrar->estado,
                'pagos' => $cuentaPorCobrar->venta->pagos->map(function ($pago) {
                    return [
                        'codigo_pago' => $pago->codigo_pago,
                        'monto' => $pago->monto,
                        'fecha_pago' => $pago->fecha_pago->format('d/m/Y H:i'),
                        'metodo_pago' => $pago->metodo_pago,
                        'referencia' => $pago->referencia,
                        'usuario' => $pago->usuarioRegistro->name,
                    ];
                }),
                'cuotas' => $cuentaPorCobrar->cuotas->map(function ($cuota) {
                    return [
                        'codigo_cuota' => $cuota->codigo_cuota,
                        'numero_cuota' => $cuota->numero_cuota,
                        'monto' => $cuota->monto,
                        'fecha_vencimiento' => $cuota->fecha_vencimiento->format('d/m/Y'),
                        'fecha_pago' => $cuota->fecha_pago?->format('d/m/Y'),
                        'estado' => $cuota->estado,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Generar plan de cuotas
     */
    public function generarCuotas(Request $request, CuentaPorCobrar $cuentaPorCobrar)
    {
        $validated = $request->validate([
            'numero_cuotas' => 'required|integer|min:1|max:12',
            'fecha_primera_cuota' => 'required|date|after:today',
        ], [
            'numero_cuotas.required' => 'El número de cuotas es obligatorio.',
            'numero_cuotas.min' => 'Debe haber al menos 1 cuota.',
            'numero_cuotas.max' => 'No puede haber más de 12 cuotas.',
            'fecha_primera_cuota.required' => 'La fecha de la primera cuota es obligatoria.',
            'fecha_primera_cuota.after' => 'La fecha debe ser posterior a hoy.',
        ]);

        DB::beginTransaction();
        try {
            // Eliminar cuotas existentes si las hay
            $cuentaPorCobrar->cuotas()->delete();

            $numeroCuotas = $validated['numero_cuotas'];
            $montoCuota = round($cuentaPorCobrar->saldo_pendiente / $numeroCuotas, 2);
            $fechaCuota = \Carbon\Carbon::parse($validated['fecha_primera_cuota']);

            for ($i = 1; $i <= $numeroCuotas; $i++) {
                // Ajustar última cuota para compensar redondeos
                $monto = ($i === $numeroCuotas)
                    ? $cuentaPorCobrar->saldo_pendiente - ($montoCuota * ($numeroCuotas - 1))
                    : $montoCuota;

                Cuota::create([
                    'cuenta_por_cobrar' => $cuentaPorCobrar->codigo_cuenta,
                    'numero_cuota' => $i,
                    'monto' => $monto,
                    'fecha_vencimiento' => $fechaCuota->copy(),
                    'estado' => 'pendiente',
                ]);

                $fechaCuota->addMonth();
            }

            DB::commit();

            return redirect()->back()
                ->with('success', 'Plan de cuotas generado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al generar cuotas: ' . $e->getMessage());
        }
    }
}
