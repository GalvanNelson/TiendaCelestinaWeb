<?php

namespace App\Services;

use App\Models\CuentaPorCobrar;
use App\Models\Cuota;
use Carbon\Carbon;

class CuotasService
{
    /**
     * Generar cuotas para una venta a crédito
     *
     * @param CuentaPorCobrar $cuentaPorCobrar
     * @param int $numeroCuotas Mínimo 2 cuotas
     * @param float|null $montoPrimeraCuota Opcional, si el cliente paga un monto inicial diferente
     * @return array
     */
    public function generarCuotas(
        CuentaPorCobrar $cuentaPorCobrar,
        int $numeroCuotas = 2,
        ?float $montoPrimeraCuota = null
    ): array {

        // Validar mínimo 2 cuotas
        if ($numeroCuotas < 2) {
            throw new \InvalidArgumentException('El número mínimo de cuotas es 2');
        }

        $montoTotal = $cuentaPorCobrar->monto_total;
        $cuotas = [];

        // Si hay un monto inicial diferente para la primera cuota
        if ($montoPrimeraCuota && $montoPrimeraCuota > 0 && $montoPrimeraCuota < $montoTotal) {
            // Primera cuota con monto personalizado
            $cuotas[] = [
                'numero_cuota' => 1,
                'monto' => $montoPrimeraCuota,
                'fecha_vencimiento' => Carbon::now()->addDays(15), // 15 días
            ];

            // Distribuir el resto en las cuotas restantes
            $montoRestante = $montoTotal - $montoPrimeraCuota;
            $montoPorCuota = round($montoRestante / ($numeroCuotas - 1), 2);

            for ($i = 2; $i <= $numeroCuotas; $i++) {
                $cuotas[] = [
                    'numero_cuota' => $i,
                    'monto' => $montoPorCuota,
                    'fecha_vencimiento' => Carbon::now()->addDays(15 * $i),
                ];
            }

            // Ajustar última cuota por redondeos
            $totalCuotas = array_sum(array_column($cuotas, 'monto'));
            if ($totalCuotas != $montoTotal) {
                $cuotas[count($cuotas) - 1]['monto'] += ($montoTotal - $totalCuotas);
            }

        } else {
            // Distribuir el monto total equitativamente
            $montoPorCuota = round($montoTotal / $numeroCuotas, 2);

            for ($i = 1; $i <= $numeroCuotas; $i++) {
                $cuotas[] = [
                    'numero_cuota' => $i,
                    'monto' => $montoPorCuota,
                    'fecha_vencimiento' => Carbon::now()->addDays(15 * $i),
                ];
            }

            // Ajustar última cuota por redondeos
            $totalCuotas = array_sum(array_column($cuotas, 'monto'));
            if ($totalCuotas != $montoTotal) {
                $cuotas[count($cuotas) - 1]['monto'] += ($montoTotal - $totalCuotas);
            }
        }

        // Crear las cuotas en la base de datos
        $cuotasCreadas = [];
        foreach ($cuotas as $datosCuota) {
            $cuotasCreadas[] = Cuota::create([
                'cuenta_por_cobrar_id' => $cuentaPorCobrar->codigo_cuenta,
                'numero_cuota' => $datosCuota['numero_cuota'],
                'monto' => $datosCuota['monto'],
                'fecha_vencimiento' => $datosCuota['fecha_vencimiento'],
                'estado' => 'pendiente',
            ]);
        }

        return $cuotasCreadas;
    }

    /**
     * Aplicar pago a una cuenta por cobrar
     * El cliente puede pagar cualquier monto (no necesariamente una cuota completa)
     *
     * @param CuentaPorCobrar $cuentaPorCobrar
     * @param float $montoPago
     * @return array Información del pago aplicado
     */
    public function aplicarPago(CuentaPorCobrar $cuentaPorCobrar, float $montoPago): array
    {
        if ($montoPago <= 0) {
            throw new \InvalidArgumentException('El monto del pago debe ser mayor a 0');
        }

        if ($montoPago > $cuentaPorCobrar->saldo_pendiente) {
            throw new \InvalidArgumentException('El monto del pago excede el saldo pendiente');
        }

        $montoRestante = $montoPago;
        $cuotasPagadas = [];
        $cuotasParciales = [];

        // Obtener cuotas pendientes ordenadas por fecha de vencimiento
        $cuotasPendientes = $cuentaPorCobrar->cuotas()
            ->whereIn('estado', ['pendiente', 'vencida'])
            ->orderBy('fecha_vencimiento')
            ->get();

        foreach ($cuotasPendientes as $cuota) {
            if ($montoRestante <= 0) {
                break;
            }

            if ($montoRestante >= $cuota->monto) {
                // Pagar cuota completa
                $cuota->update([
                    'estado' => 'pagada',
                    'fecha_pago' => now(),
                ]);
                $cuotasPagadas[] = $cuota;
                $montoRestante -= $cuota->monto;

            } else {
                // Pago parcial de la cuota
                $montoParcial = $montoRestante;
                $nuevoMonto = $cuota->monto - $montoParcial;

                $cuota->update([
                    'monto' => $nuevoMonto,
                ]);

                $cuotasParciales[] = [
                    'cuota' => $cuota,
                    'monto_pagado' => $montoParcial,
                    'saldo_restante' => $nuevoMonto,
                ];

                $montoRestante = 0;
            }
        }

        // Actualizar cuenta por cobrar
        $cuentaPorCobrar->monto_pagado += $montoPago;
        $cuentaPorCobrar->saldo_pendiente = $cuentaPorCobrar->monto_total - $cuentaPorCobrar->monto_pagado;

        // Actualizar estado
        if ($cuentaPorCobrar->saldo_pendiente <= 0) {
            $cuentaPorCobrar->estado = 'pagado';
        } elseif ($cuentaPorCobrar->monto_pagado > 0) {
            $cuentaPorCobrar->estado = 'parcial';
        }

        $cuentaPorCobrar->save();

        // Calcular próxima fecha de pago (15 días desde hoy)
        $proximaFechaPago = Carbon::now()->addDays(15);
        $proximaCuota = $cuentaPorCobrar->cuotas()
            ->whereIn('estado', ['pendiente', 'vencida'])
            ->orderBy('fecha_vencimiento')
            ->first();

        return [
            'monto_pagado' => $montoPago,
            'saldo_pendiente' => $cuentaPorCobrar->saldo_pendiente,
            'cuotas_pagadas' => $cuotasPagadas,
            'cuotas_parciales' => $cuotasParciales,
            'proxima_fecha_pago' => $proximaFechaPago,
            'proxima_cuota' => $proximaCuota,
            'cuenta_saldada' => $cuentaPorCobrar->saldo_pendiente <= 0,
        ];
    }

    /**
     * Obtener información del próximo pago
     *
     * @param CuentaPorCobrar $cuentaPorCobrar
     * @return array|null
     */
    public function obtenerProximoPago(CuentaPorCobrar $cuentaPorCobrar): ?array
    {
        if ($cuentaPorCobrar->estado === 'pagado') {
            return null;
        }

        $proximaCuota = $cuentaPorCobrar->cuotas()
            ->whereIn('estado', ['pendiente', 'vencida'])
            ->orderBy('fecha_vencimiento')
            ->first();

        if (!$proximaCuota) {
            return null;
        }

        $diasRestantes = Carbon::now()->diffInDays($proximaCuota->fecha_vencimiento, false);
        $estaVencida = $diasRestantes < 0;

        return [
            'cuota' => $proximaCuota,
            'monto_minimo' => 0.1, // El cliente puede pagar cualquier monto desde 0.1 BOB
            'monto_cuota' => $proximaCuota->monto,
            'monto_total_pendiente' => $cuentaPorCobrar->saldo_pendiente,
            'fecha_vencimiento' => $proximaCuota->fecha_vencimiento,
            'dias_restantes' => abs($diasRestantes),
            'esta_vencida' => $estaVencida,
            'puede_pagar_parcial' => true, // Siempre puede pagar parcial
        ];
    }

    /**
     * Marcar cuotas vencidas
     * Este método debe ejecutarse diariamente (via cron/scheduler)
     */
    public function marcarCuotasVencidas(): int
    {
        return Cuota::where('estado', 'pendiente')
            ->where('fecha_vencimiento', '<', Carbon::now())
            ->update(['estado' => 'vencida']);
    }

    /**
     * Obtener resumen de cuotas
     *
     * @param CuentaPorCobrar $cuentaPorCobrar
     * @return array
     */
    public function obtenerResumenCuotas(CuentaPorCobrar $cuentaPorCobrar): array
    {
        $cuotas = $cuentaPorCobrar->cuotas()->orderBy('numero_cuota')->get();

        return [
            'total_cuotas' => $cuotas->count(),
            'cuotas_pagadas' => $cuotas->where('estado', 'pagada')->count(),
            'cuotas_pendientes' => $cuotas->where('estado', 'pendiente')->count(),
            'cuotas_vencidas' => $cuotas->where('estado', 'vencida')->count(),
            'monto_total' => $cuentaPorCobrar->monto_total,
            'monto_pagado' => $cuentaPorCobrar->monto_pagado,
            'saldo_pendiente' => $cuentaPorCobrar->saldo_pendiente,
            'porcentaje_pagado' => $cuentaPorCobrar->monto_total > 0
                ? round(($cuentaPorCobrar->monto_pagado / $cuentaPorCobrar->monto_total) * 100, 2)
                : 0,
            'cuotas' => $cuotas->map(function ($cuota) {
                return [
                    'numero' => $cuota->numero_cuota,
                    'monto' => $cuota->monto,
                    'fecha_vencimiento' => $cuota->fecha_vencimiento->format('d/m/Y'),
                    'fecha_pago' => $cuota->fecha_pago?->format('d/m/Y'),
                    'estado' => $cuota->estado,
                    'dias_vencimiento' => Carbon::now()->diffInDays($cuota->fecha_vencimiento, false),
                ];
            }),
        ];
    }
}
