<?php

namespace Tests\Feature;

use App\Models\Entidad;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UsuariosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Entidad $entidad;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(PermissionSeeder::class);

        $this->entidad = Entidad::create([
            'codigo' => '001',
            'nombre' => 'Entidad de Prueba',
            'abreviatura' => 'EPRUEBA',
        ]);

        $this->admin = User::factory()->create(['id_entidad' => $this->entidad->id]);
        $this->admin->assignRole('SUPERADMIN');
    }

    private function datosUsuario(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Usuario Prueba',
            'username' => 'uprueba',
            'email' => null,
            'password' => 'temporal123',
            'role' => 'TECNICA',
            'id_entidad' => $this->entidad->id,
            'idgrupo' => null,
        ], $overrides);
    }

    public function test_lista_usuarios_requiere_permiso(): void
    {
        $sinPermiso = User::factory()->create();

        $this->actingAs($sinPermiso)->get('/usuarios')->assertForbidden();
        $this->actingAs($this->admin)->get('/usuarios')->assertOk();
    }

    public function test_crear_usuario_con_perfil_y_password_temporal(): void
    {
        $response = $this->actingAs($this->admin)->post('/usuarios', $this->datosUsuario());

        $response->assertRedirect('/usuarios');

        $user = User::where('username', 'UPRUEBA')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->password_temporal);
        $this->assertTrue($user->hasRole('TECNICA'));
        $this->assertDatabaseHas('bitacora', ['accion' => 'crear_usuario']);
    }

    public function test_no_permite_username_duplicado(): void
    {
        User::factory()->create(['username' => 'UPRUEBA']);

        $response = $this->actingAs($this->admin)->post('/usuarios', $this->datosUsuario());

        $response->assertSessionHasErrors('username');
    }

    public function test_actualizar_usuario_cambia_datos_y_perfil(): void
    {
        $user = User::factory()->create();
        $user->assignRole('TECNICA');

        $response = $this->actingAs($this->admin)->put("/usuarios/{$user->id}", $this->datosUsuario([
            'name' => 'Nombre Editado',
            'username' => $user->username,
            'role' => 'COMERCIAL',
        ]));

        $response->assertRedirect('/usuarios');
        $this->assertEquals('Nombre Editado', $user->fresh()->name);
        $this->assertTrue($user->fresh()->hasRole('COMERCIAL'));
        $this->assertFalse($user->fresh()->hasRole('TECNICA'));
        $this->assertDatabaseHas('bitacora', ['accion' => 'editar_usuario']);
    }

    public function test_eliminar_usuario_es_soft_delete(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/usuarios/{$user->id}");

        $response->assertRedirect('/usuarios');
        $this->assertSoftDeleted($user);
        $this->assertDatabaseHas('bitacora', ['accion' => 'eliminar_usuario']);
    }

    public function test_no_puede_eliminarse_a_si_mismo(): void
    {
        $response = $this->actingAs($this->admin)->delete("/usuarios/{$this->admin->id}");

        $response->assertSessionHas('error');
        $this->assertNotNull($this->admin->fresh());
    }

    public function test_desbloquear_limpia_bloqueo_e_intentos(): void
    {
        $user = User::factory()->create(['bloqueado' => true, 'intentos_fallidos' => 5]);

        $response = $this->actingAs($this->admin)->post("/usuarios/{$user->id}/desbloquear");

        $response->assertRedirect('/usuarios');
        $user->refresh();
        $this->assertFalse($user->bloqueado);
        $this->assertEquals(0, $user->intentos_fallidos);
        $this->assertDatabaseHas('bitacora', ['accion' => 'desbloquear_usuario']);
    }

    public function test_restablecer_password_marca_temporal_y_desbloquea(): void
    {
        $user = User::factory()->create(['bloqueado' => true, 'intentos_fallidos' => 3]);

        $response = $this->actingAs($this->admin)->post("/usuarios/{$user->id}/restablecer-password", [
            'password' => 'nuevaTemporal1',
        ]);

        $response->assertRedirect('/usuarios');
        $user->refresh();
        $this->assertTrue($user->password_temporal);
        $this->assertFalse($user->bloqueado);
        $this->assertEquals(0, $user->intentos_fallidos);
        $this->assertDatabaseHas('bitacora', ['accion' => 'restablecer_password']);
    }

    public function test_usuario_tecnica_no_puede_gestionar_usuarios(): void
    {
        $tecnica = User::factory()->create();
        $tecnica->assignRole('TECNICA');
        $objetivo = User::factory()->create();

        $this->actingAs($tecnica)->get('/usuarios')->assertForbidden();
        $this->actingAs($tecnica)->post('/usuarios', $this->datosUsuario())->assertForbidden();
        $this->actingAs($tecnica)->put("/usuarios/{$objetivo->id}", $this->datosUsuario())->assertForbidden();
        $this->actingAs($tecnica)->delete("/usuarios/{$objetivo->id}")->assertForbidden();
        $this->actingAs($tecnica)->post("/usuarios/{$objetivo->id}/desbloquear")->assertForbidden();
    }

    public function test_login_funciona_con_username_en_minusculas(): void
    {
        // El legacy guarda los logins en mayúsculas; el login debe
        // encontrarlos aunque el usuario escriba en minúsculas.
        $this->actingAs($this->admin)->post('/usuarios', $this->datosUsuario());
        $this->post('/logout');

        $response = $this->post('/login', [
            'username' => 'uprueba',
            'password' => 'temporal123',
        ]);

        // password temporal → redirige al cambio, pero autenticó
        $response->assertRedirect(route('password.edit'));
        $this->assertAuthenticated();
    }
}
