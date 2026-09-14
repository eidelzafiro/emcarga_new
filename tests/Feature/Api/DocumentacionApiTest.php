<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\TestCase;

/**
 * Fase 10: documentación OpenAPI (Scramble) en /docs/api y /docs/api.json,
 * restringida a entorno local o usuarios SUPERADMIN.
 */
class DocumentacionApiTest extends TestCase
{
    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('SUPERADMIN');

        return $user;
    }

    public function test_docs_denegado_a_visitantes(): void
    {
        $this->getJson('/docs/api.json')->assertForbidden();
    }

    public function test_docs_accesible_para_superadmin(): void
    {
        $this->actingAs($this->superadmin())
            ->getJson('/docs/api.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.1.0');
    }

    public function test_spec_incluye_endpoints_de_negocio_y_bearer(): void
    {
        $response = $this->actingAs($this->superadmin())->getJson('/docs/api.json');
        $response->assertOk();

        $paths = array_keys($response->json('paths'));
        $this->assertContains('/tractivos', $paths);
        $this->assertContains('/auth/login', $paths);

        $this->assertArrayHasKey('http', $response->json('components.securitySchemes'));
    }
}
