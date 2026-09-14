<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Endurecimiento de la API v1: cabeceras de seguridad, rate limiting,
 * expiración de tokens y CORS restringido.
 */
class SeguridadApiTest extends TestCase
{
    public function test_respuesta_api_incluye_cabeceras_de_seguridad(): void
    {
        $response = $this->getJson('/api/v1/ping');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_endpoints_autenticados_aplican_throttle_api(): void
    {
        $ruta = collect(Route::getRoutes())->first(
            fn ($r) => $r->uri() === 'api/v1/tractivos'
        );

        $this->assertNotNull($ruta, 'La ruta api/v1/tractivos debe existir.');
        $this->assertContains('throttle:api', $ruta->middleware());
    }

    public function test_login_aplica_throttle_login(): void
    {
        $ruta = collect(Route::getRoutes())->first(
            fn ($r) => $r->uri() === 'api/v1/auth/login'
        );

        $this->assertNotNull($ruta);
        $this->assertContains('throttle:login', $ruta->middleware());
    }

    public function test_tokens_tienen_expiracion_configurada(): void
    {
        $this->assertIsInt(config('sanctum.expiration'));
        $this->assertGreaterThan(0, config('sanctum.expiration'));
    }

    public function test_cors_restringido_a_origenes_configurados(): void
    {
        $origenes = config('cors.allowed_origins');

        $this->assertIsArray($origenes);
        $this->assertNotEmpty($origenes);
        $this->assertNotContains('*', $origenes, 'CORS no debe permitir cualquier origen.');
    }
}
