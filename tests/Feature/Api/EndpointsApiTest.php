<?php

namespace Tests\Feature\Api;

use App\Models\Entidad;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Smoke de los endpoints de negocio de la API v1: cada índice debe responder
 * 200 con un token válido (detecta errores SQL, relaciones inexistentes y
 * columnas faltantes) y 401 sin token.
 */
class EndpointsApiTest extends TestCase
{
    private function usuario(): array
    {
        $entidad = Entidad::create([
            'nombre' => 'ENTIDAD API SMOKE',
            'abreviatura' => 'EAS',
            'activo' => true,
            'es_matriz' => false,
        ]);

        $user = User::factory()->create(['id_entidad' => $entidad->id]);
        $token = $user->createToken('test', ["entidad:{$entidad->id}", 'fecha:'.now()->format('Y-m')])->plainTextToken;

        return [$user, $token];
    }

    /** @return array<string,array{0:string}> */
    public static function endpoints(): array
    {
        return [
            'tractivos' => ['/api/v1/tractivos'],
            'bolsa' => ['/api/v1/bolsa'],
            'cargos' => ['/api/v1/cargos'],
            'salarios' => ['/api/v1/salarios'],
            'clientes' => ['/api/v1/clientes'],
            'lugares' => ['/api/v1/lugares'],
            'acuerdos' => ['/api/v1/acuerdos'],
            'aforos' => ['/api/v1/aforos'],
            'facturas' => ['/api/v1/facturas'],
            'indicadores' => ['/api/v1/indicadores'],
            'combustible/cargas' => ['/api/v1/combustible/cargas'],
            'combustible/descargas' => ['/api/v1/combustible/descargas'],
            'tarjetas' => ['/api/v1/tarjetas'],
            'reportes-costos' => ['/api/v1/reportes-costos'],
            'taller/ordenes' => ['/api/v1/taller/ordenes'],
            'taller/control-lubricantes' => ['/api/v1/taller/control-lubricantes'],
        ];
    }

    /**
     * @dataProvider endpoints
     */
    #[DataProvider('endpoints')]
    public function test_endpoint_responde_200_con_token(string $uri): void
    {
        [, $token] = $this->usuario();

        $response = $this->withToken($token)->getJson($uri);

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    /**
     * @dataProvider endpoints
     */
    #[DataProvider('endpoints')]
    public function test_endpoint_requiere_token(string $uri): void
    {
        $this->getJson($uri)->assertUnauthorized();
    }

    public function test_paginacion_per_page_se_limita_a_100(): void
    {
        [, $token] = $this->usuario();

        $response = $this->withToken($token)->getJson('/api/v1/clientes?per_page=500');
        $response->assertOk();

        $this->assertLessThanOrEqual(100, $response->json('meta.per_page'));
    }

    public function test_auth_me_devuelve_usuario(): void
    {
        [, $token] = $this->usuario();

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonStructure(['user', 'perfil_activo', 'entidades']);
    }

    public function test_aforos_usa_paginacion_por_cursor(): void
    {
        [, $token] = $this->usuario();

        $response = $this->withToken($token)->getJson('/api/v1/aforos');
        $response->assertOk();
        $response->assertJsonStructure(['data', 'meta' => ['per_page', 'next_cursor', 'prev_cursor']]);
    }
}
