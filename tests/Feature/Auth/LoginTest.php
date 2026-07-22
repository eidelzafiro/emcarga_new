<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_el_formulario_de_login(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_redirige_a_login_si_no_esta_autenticado(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_exitoso_redirige_al_dashboard(): void
    {
        $user = User::factory()->create(['password' => 'Secreto*1']);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'Secreto*1',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertEquals(0, $user->fresh()->intentos_fallidos);
        $this->assertNotNull($user->fresh()->ultimo_login);
        $this->assertDatabaseHas('bitacora', ['user_id' => $user->id, 'accion' => 'login']);
    }

    public function test_password_incorrecta_incrementa_intentos_fallidos(): void
    {
        $user = User::factory()->create(['password' => 'Secreto*1']);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'incorrecta',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertEquals(1, $user->fresh()->intentos_fallidos);
        $this->assertGuest();
        $this->assertDatabaseHas('bitacora', ['user_id' => $user->id, 'accion' => 'login_fallido']);
    }

    public function test_bloqueo_tras_cinco_intentos_fallidos(): void
    {
        $user = User::factory()->create(['password' => 'Secreto*1']);

        for ($i = 0; $i < User::MAX_INTENTOS_LOGIN; $i++) {
            $this->post('/login', [
                'username' => $user->username,
                'password' => 'incorrecta',
            ]);
        }

        $this->assertTrue($user->fresh()->estaBloqueado());
        $this->assertDatabaseHas('bitacora', ['user_id' => $user->id, 'accion' => 'bloqueo_automatico']);

        // Aun con la contraseña correcta ya no puede entrar
        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'Secreto*1',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_usuario_bloqueado_por_admin_no_puede_entrar(): void
    {
        $user = User::factory()->bloqueado()->create(['password' => 'Secreto*1']);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'Secreto*1',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->assertDatabaseHas('bitacora', ['user_id' => $user->id, 'accion' => 'login_bloqueado']);
    }

    public function test_usuario_inexistente_da_mensaje_generico(): void
    {
        $response = $this->post('/login', [
            'username' => 'noexiste',
            'password' => 'Secreto*1',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_password_temporal_redirige_al_cambio_de_password(): void
    {
        $user = User::factory()->conPasswordTemporal()->create(['password' => 'Secreto*1']);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'Secreto*1',
        ]);

        $response->assertRedirect(route('password.edit'));
    }

    public function test_middleware_fuerza_cambio_de_password_temporal(): void
    {
        $user = User::factory()->conPasswordTemporal()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('password.edit'));
    }

    public function test_logout_cierra_la_sesion(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
        $this->assertDatabaseHas('bitacora', ['user_id' => $user->id, 'accion' => 'logout']);
    }
}
