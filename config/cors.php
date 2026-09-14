<?php

/*
|--------------------------------------------------------------------------
| CORS
|--------------------------------------------------------------------------
|
| La API móvil v1 usa autenticación por token Bearer (Sanctum), sin cookies,
| por lo que no necesita `supports_credentials`. El origen del cliente Ionic
| (web build / Capacitor) se configura con MOBILE_ORIGINS (lista separada por
| comas). Los orígenes de desarrollo locales se incluyen por defecto.
|
| Nota: las apps nativas (Capacitor) NO aplican CORS; esta config solo afecta
| a los builds web del cliente y a cualquier consumidor en navegador.
|
*/

$origenes = array_filter(array_map('trim', explode(',', (string) env(
    'MOBILE_ORIGINS',
    'http://localhost:8100,http://localhost:5173'
))));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $origenes,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,
];
