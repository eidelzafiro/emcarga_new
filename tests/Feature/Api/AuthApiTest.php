<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    private function usuario(string $password = 'Secreta123'): User
    {
        $user = User::factory()->create([
            'username' => 'PRUEBA_API',
            'password' => Hash::make($password),
            'password_temporal' => false,
        ]);

        return $user;
    }

    public function test_ping_responde_ok(): void
    {
        $this->getJson('/api/v1/ping')
            ->assertOk()
            ->assertJson(['ok' => true, 'version' => 'v1']);
    }

    public function test_login_devuelve_token(): void
    {
        $this->usuario();

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'prueba_api', // minúsculas: debe subir a mayúsculas
            'password' => 'Secreta123',
            'device_name' => 'pixel',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'username'], 'entidades'])
            ->assertJsonPath('token_type', 'Bearer');
    }

    public function test_login_credenciales_invalidas(): void
    {
        $this->usuario();

        $this->postJson('/api/v1/auth/login', [
            'username' => 'PRUEBA_API',
            'password' => 'incorrecta',
        ])->assertStatus(422)->assertJsonValidationErrors('username');
    }

    public function test_me_requiere_token(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_y_logout_con_token(): void
    {
        $user = $this->usuario();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.username', 'PRUEBA_API');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        // Sanctum cachea el token resuelto en el guard dentro del mismo test:
        // se olvidan los guards para que la siguiente petición lo revalide.
        $this->app['auth']->forgetGuards();

        // El token quedó revocado.
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }
}
