<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PagoFacilService
{
    private string $baseUrl;
    private string $tokenService;
    private string $tokenSecret;
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->baseUrl = config('pagofacil.base_url');
        $this->tokenService = config('pagofacil.credentials.token_service');
        $this->tokenSecret = config('pagofacil.credentials.token_secret');
    }

    /**
     * Autenticar y obtener token
     */
    public function authenticate(): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'tcTokenService' => $this->tokenService,
                    'tcTokenSecret' => $this->tokenSecret,
                ])
                ->post("{$this->baseUrl}/login");

            if (!$response->successful()) {
                throw new \Exception('Error en autenticación: ' . $response->body());
            }

            $data = $response->json();

            if ($data['error'] !== 0) {
                throw new \Exception($data['message'] ?? 'Error desconocido en autenticación');
            }

            $this->accessToken = $data['values']['accessToken'];

            // Cachear el token
            $expiresIn = $data['values']['expiresInMinutes'] ?? 720;
            Cache::put('pagofacil_access_token', $this->accessToken, now()->addMinutes($expiresIn - 10));

            return $data['values'];

        } catch (\Exception $e) {
            Log::error('PagoFácil Authentication Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener token (desde cache o autenticando)
     */
    private function getToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        // Intentar obtener desde cache
        $cachedToken = Cache::get('pagofacil_access_token');
        if ($cachedToken) {
            $this->accessToken = $cachedToken;
            return $cachedToken;
        }

        // Autenticar y obtener nuevo token
        $authData = $this->authenticate();
        return $authData['accessToken'];
    }

    /**
     * Listar métodos de pago habilitados
     */
    public function listEnabledServices(): array
    {
        try {
            $token = $this->getToken();

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Response-Language' => config('pagofacil.payment.language', 'es'),
                ])
                ->post("{$this->baseUrl}/list-enabled-services");

            if (!$response->successful()) {
                throw new \Exception('Error al listar servicios: ' . $response->body());
            }

            $data = $response->json();

            if ($data['error'] !== 0) {
                throw new \Exception($data['message'] ?? 'Error desconocido');
            }

            return $data['values'];

        } catch (\Exception $e) {
            Log::error('PagoFácil List Services Error', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generar QR para pago
     */
    public function generateQR(array $params): array
    {
        try {
            $token = $this->getToken();

            // Validar parámetros requeridos
            $this->validateGenerateQRParams($params);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Response-Language' => config('pagofacil.payment.language', 'es'),
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/generate-qr", $params);

            if (!$response->successful()) {
                throw new \Exception('Error al generar QR: ' . $response->body());
            }

            $data = $response->json();

            if ($data['error'] !== 0) {
                throw new \Exception($data['message'] ?? 'Error desconocido al generar QR');
            }

            return $data['values'];

        } catch (\Exception $e) {
            Log::error('PagoFácil Generate QR Error', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            throw $e;
        }
    }

    /**
     * Consultar estado de transacción
     */
    public function queryTransaction(string $transactionId, bool $isPagoFacilId = true): array
    {
        try {
            $token = $this->getToken();

            $body = $isPagoFacilId
                ? ['pagofacilTransactionId' => $transactionId]
                : ['companyTransactionId' => $transactionId];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Response-Language' => config('pagofacil.payment.language', 'es'),
                ])
                ->post("{$this->baseUrl}/query-transaction", $body);

            if (!$response->successful()) {
                throw new \Exception('Error al consultar transacción: ' . $response->body());
            }

            $data = $response->json();

            if ($data['error'] !== 0) {
                throw new \Exception($data['message'] ?? 'Error desconocido');
            }

            return $data['values'];

        } catch (\Exception $e) {
            Log::error('PagoFácil Query Transaction Error', [
                'error' => $e->getMessage(),
                'transactionId' => $transactionId
            ]);
            throw $e;
        }
    }

    /**
     * Validar parámetros para generar QR
     */
    private function validateGenerateQRParams(array $params): void
    {
        $required = [
            'paymentMethod',
            'clientName',
            'documentId',
            'paymentNumber',
            'amount',
            'orderDetail'
        ];

        foreach ($required as $field) {
            if (!isset($params[$field])) {
                throw new \InvalidArgumentException("El campo {$field} es requerido");
            }
        }

        if (!is_array($params['orderDetail']) || empty($params['orderDetail'])) {
            throw new \InvalidArgumentException("orderDetail debe ser un array con al menos un elemento");
        }
    }
}
