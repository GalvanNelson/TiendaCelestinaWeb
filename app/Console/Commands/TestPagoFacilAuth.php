<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestPagoFacilAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-pago-facil-auth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar autenticación con Pago Fácil';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Probando autenticación con Pago Fácil...');

        try {
            $response = Http::withHeaders([
                'tcTokenSecret' => config('services.pagofacil.token_secret'),
                'tcTokenService' => config('services.pagofacil.token_service'),
            ])->post('https://masterqr.pagofacil.com.bo/api/services/v2/login');

            if ($response->successful()) {
                $data = $response->json();

                if ($data['error'] === 0) {
                    $this->info('✅ Autenticación exitosa!');
                    $this->info('Token expira en: ' . $data['values']['expiresInMinutes'] . ' minutos');
                    return 0;
                }
            }

            $this->error('❌ Error en autenticación');
            $this->error('Respuesta: ' . $response->body());
            return 1;

        } catch (\Exception $e) {
            $this->error('❌ Excepción: ' . $e->getMessage());
            return 1;
        }
    }
}
