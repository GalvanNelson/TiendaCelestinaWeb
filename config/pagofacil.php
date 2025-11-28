<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PagoFácil Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para la integración con PagoFácil QR Master
    |
    */

    'base_url' => env('PAGOFACIL_BASE_URL', 'https://masterqr.pagofacil.com.bo/api/services/v2'),

    'credentials' => [
        'token_service' => env('PAGOFACIL_TOKEN_SERVICE'),
        'token_secret' => env('PAGOFACIL_TOKEN_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'url' => env('PAGOFACIL_CALLBACK_URL', env('APP_URL') . '/api/pagofacil/webhook'),
        'secret' => env('PAGOFACIL_WEBHOOK_SECRET', 'change-this-secret'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */
    'payment' => [
        'currency' => 2, // 2 = BOB (Bolivianos)
        'document_type' => 1, // 1 = CI (Cédula de Identidad)
        'language' => env('PAGOFACIL_LANGUAGE', 'es'), // es = Español, en = Inglés
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Cache
    |--------------------------------------------------------------------------
    | Tiempo en minutos para cachear el token de autenticación
    */
    'token_cache_minutes' => 720, // 12 horas (el token dura más, pero por seguridad)
];
