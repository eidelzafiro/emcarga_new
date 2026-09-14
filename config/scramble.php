<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;

/*
|--------------------------------------------------------------------------
| Scramble — Documentación OpenAPI de la API móvil
|--------------------------------------------------------------------------
|
| Genera el spec OpenAPI 3.1 a partir de las rutas, FormRequests y API
| Resources. La UI queda en /docs/api y el JSON en /docs/api.json.
|
| Seguridad: se conserva RestrictedDocsAccess (solo local o vía gate
| `viewApiDocs`, definido en AppServiceProvider para SUPERADMIN).
|
*/

return [
    // Documenta solo la API móvil v1.
    'api_path' => 'api/v1',

    'api_domain' => null,

    // Spec exportable con `php artisan scramble:export`.
    'export_path' => 'docs/openapi-v1.json',

    'cache' => [
        'key' => 'scramble.openapi',
        'store' => 'file',
    ],

    'info' => [
        'version' => env('API_VERSION', 'v1'),
        'description' => 'API móvil de Zafiro (EMCARGA): autenticación por token Sanctum, '
            .'filtrado por entidad y mes de operaciones. Los endpoints de negocio usan '
            .'`Authorization: Bearer {token}`.',
    ],

    'ui' => [
        'title' => 'API móvil Zafiro',
    ],

    'dev_tools' => [
        'enabled' => env('SCRAMBLE_DEV_TOOLS', env('APP_DEBUG', false)),
    ],

    'renderer' => 'elements',

    'renderers' => [
        'elements' => [
            'view' => 'scramble::docs',
            'theme' => 'light',
            'hideTryIt' => false,
            'hideSchemas' => false,
            'logo' => '',
            'tryItCredentialsPolicy' => 'include',
            'layout' => 'responsive',
            'router' => 'hash',
        ],
        'scalar' => [
            'view' => 'scramble::scalar',
            'cdn' => 'https://cdn.jsdelivr.net/npm/@scalar/api-reference',
            'theme' => 'laravel',
            'proxyUrl' => 'https://proxy.scalar.com',
            'darkMode' => false,
            'showDeveloperTools' => 'never',
            'agent' => ['disabled' => true],
            'credentials' => 'include',
        ],
    ],

    'servers' => null,

    'enum_cases_description_strategy' => 'description',

    'enum_cases_names_strategy' => false,

    'flatten_deep_query_parameters' => true,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],

    // Documenta automáticamente Bearer auth en las rutas con `auth:sanctum`.
    'security_strategy' => MiddlewareAuthSecurityStrategy::class,
];
