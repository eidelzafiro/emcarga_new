<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PerfilesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(PermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('SUPERADMIN');
    }

    public function test_lista_perfiles_requiere_permiso(): void
    {
        $sinPermiso = User::factory()->create();

        $this->actingAs($sinPermiso)->get('/perfiles')->assertForbidden();
        $this->actingAs($this->admin)->get('/perfiles')->assertOk();
    }

    public function test_crear_perfil_con_permisos(): void
    {
        $response = $this->actingAs($this->admin)->post('/perfiles', [
            'nombre' => 'operaciones2',
            'permisos' => ['dashboard.ver', 'tractivos.ver'],
        ]);

        $response->assertRedirect('/perfiles');

        $perfil = Role::where('name', 'OPERACIONES2')->first();
        $this->assertNotNull($perfil);
        $this->assertTrue($perfil->hasPermissionTo('dashboard.ver'));
        $this->assertTrue($perfil->hasPermissionTo('tractivos.ver'));
        $this->assertFalse($perfil->hasPermissionTo('usuarios.ver'));
        $this->assertDatabaseHas('bitacora', ['accion' => 'crear_perfil']);
    }

    public function test_no_permite_nombre_duplicado_sin_importar_mayusculas(): void
    {
        $response = $this->actingAs($this->admin)->post('/perfiles', [
            'nombre' => 'tecnica',
            'permisos' => [],
        ]);

        $response->assertSessionHasErrors('nombre');
    }

    public function test_actualizar_perfil_sincroniza_permisos(): void
    {
        $perfil = Role::create(['name' => 'AUXILIAR']);
        $perfil->syncPermissions(['dashboard.ver']);

        $response = $this->actingAs($this->admin)->put("/perfiles/{$perfil->id}", [
            'nombre' => 'AUXILIAR',
            'permisos' => ['tractivos.ver'],
        ]);

        $response->assertRedirect('/perfiles');
        $perfil->refresh();
        $this->assertTrue($perfil->hasPermissionTo('tractivos.ver'));
        $this->assertFalse($perfil->hasPermissionTo('dashboard.ver'));
        $this->assertDatabaseHas('bitacora', ['accion' => 'editar_perfil']);
    }

    public function test_no_se_puede_renombrar_superadmin(): void
    {
        $perfil = Role::where('name', 'SUPERADMIN')->first();

        $response = $this->actingAs($this->admin)->put("/perfiles/{$perfil->id}", [
            'nombre' => 'ROOT',
            'permisos' => ['dashboard.ver'],
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals('SUPERADMIN', $perfil->fresh()->name);
    }

    public function test_no_se_puede_eliminar_superadmin(): void
    {
        $perfil = Role::where('name', 'SUPERADMIN')->first();

        $response = $this->actingAs($this->admin)->delete("/perfiles/{$perfil->id}");

        $response->assertSessionHas('error');
        $this->assertNotNull($perfil->fresh());
    }

    public function test_no_se_puede_eliminar_perfil_con_usuarios(): void
    {
        $perfil = Role::where('name', 'TECNICA')->first();
        $usuario = User::factory()->create();
        $usuario->assignRole('TECNICA');

        $response = $this->actingAs($this->admin)->delete("/perfiles/{$perfil->id}");

        $response->assertSessionHas('error');
        $this->assertNotNull($perfil->fresh());
    }

    public function test_eliminar_perfil_sin_usuarios(): void
    {
        $perfil = Role::create(['name' => 'TEMPORAL']);

        $response = $this->actingAs($this->admin)->delete("/perfiles/{$perfil->id}");

        $response->assertRedirect('/perfiles');
        $this->assertNull(Role::where('name', 'TEMPORAL')->first());
        $this->assertDatabaseHas('bitacora', ['accion' => 'eliminar_perfil']);
    }
}
