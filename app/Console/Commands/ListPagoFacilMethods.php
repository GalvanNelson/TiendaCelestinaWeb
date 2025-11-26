<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ListPagoFacilMethods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:list-pago-facil-methods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listar métodos de pago habilitados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Obteniendo métodos de pago habilitados...');

        try {
            // Primero autenticar
            $authResponse = Http::withHeaders([
                'tcTokenSecret' => config('services.pagofacil.token_secret'),
                'tcTokenService' => config('services.pagofacil.token_service'),
            ])->post('https://masterqr.pagofacil.com.bo/api/services/v2/login');

            if (!$authResponse->successful() || $authResponse->json()['error'] !== 0) {
                $this->error('Error en autenticación');
                return 1;
            }

            $token = $authResponse->json()['values']['accessToken'];

            // Listar métodos
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Response-Language' => 'es'
            ])->post('https://masterqr.pagofacil.com.bo/api/services/v2/list-enabled-services');

            if ($response->successful()) {
                $data = $response->json();

                if ($data['error'] === 0) {
                    $this->info('✅ Métodos habilitados:');

                    foreach ($data['values'] as $method) {
                        $this->line('');
                        $this->info('ID: ' . $method['paymentMethodId']);
                        $this->line('Nombre: ' . $method['paymentMethodName']);
                        $this->line('Moneda: ' . $method['currencyName']);
                        $this->line('Máx por día: ' . $method['maxAmountPerDay']);
                        $this->line('Máx por transacción: ' . $method['maxAmountPerTransaction']);
                        $this->line('---');
                    }

                    return 0;
                }
            }

            $this->error('❌ Error al listar métodos');
            return 1;

        } catch (\Exception $e) {
            $this->error('❌ Excepción: ' . $e->getMessage());
            return 1;
        }
    }
}
