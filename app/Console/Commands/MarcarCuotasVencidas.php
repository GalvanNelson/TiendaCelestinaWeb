<?php

namespace App\Console\Commands;

use App\Services\CuotasService;
use Illuminate\Console\Command;

class MarcarCuotasVencidas extends Command
{
    protected $signature = 'cuotas:marcar-vencidas';
    protected $description = 'Marca las cuotas pendientes como vencidas cuando pasa su fecha de vencimiento';

    public function handle(CuotasService $cuotasService): int
    {
        $this->info('Marcando cuotas vencidas...');

        $cuotasActualizadas = $cuotasService->marcarCuotasVencidas();

        $this->info("✓ {$cuotasActualizadas} cuotas marcadas como vencidas.");

        return Command::SUCCESS;
    }
}
