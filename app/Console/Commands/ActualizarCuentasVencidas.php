<?php

namespace App\Console\Commands;

use App\Models\CuentaPorCobrar;
use App\Models\Cuota;
use Illuminate\Console\Command;

class ActualizarCuentasVencidas extends Command
{
    protected $signature = 'cuentas:actualizar-vencidas';
    protected $description = 'Actualiza el estado de cuentas por cobrar y cuotas vencidas';

    public function handle()
    {
        // Actualizar cuentas por cobrar vencidas
        $cuentasActualizadas = CuentaPorCobrar::where('estado', 'pendiente')
            ->where('saldo_pendiente', '>', 0)
            ->where('fecha_vencimiento', '<', now())
            ->update(['estado' => 'vencido']);

        // Actualizar cuotas vencidas
        $cuotasActualizadas = Cuota::where('estado', 'pendiente')
            ->where('fecha_vencimiento', '<', now())
            ->update(['estado' => 'vencida']);

        $this->info("Cuentas actualizadas: {$cuentasActualizadas}");
        $this->info("Cuotas actualizadas: {$cuotasActualizadas}");

        return Command::SUCCESS;
    }
}
