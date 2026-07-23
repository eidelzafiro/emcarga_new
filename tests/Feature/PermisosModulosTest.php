<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobertura del middleware EnsureModulePermission (P1-1):
 * enforcement de permisos en servidor por nombre de ruta.
 */
class PermisosModulosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function usuarioConRol(string $rol): User
    {
        $user = User::factory()->create();
        $user->assignRole($rol);
        $user->password_temporal = false;
        $user->save();

        return $user;
    }

    private function usuarioSinRol(): User
    {
        $user = User::factory()->create();
        $user->password_temporal = false;
        $user->save();

        return $user;
    }

    public function test_usuario_sin_permiso_ver_recibe_403_en_modulo_ajeno(): void
    {
        // TECNICA no tiene permisos de facturas
        $this->actingAs($this->usuarioConRol('TECNICA'))
            ->get(route('facturas.index'))
            ->assertForbidden();
    }

    public function test_usuario_con_permiso_accede_al_modulo(): void
    {
        $this->actingAs($this->usuarioConRol('COMERCIAL'))
            ->get(route('facturas.index'))
            ->assertOk();
    }

    public function test_usuario_sin_rol_no_accede_al_dashboard(): void
    {
        $this->actingAs($this->usuarioSinRol())
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_escritura_sin_permiso_crear_es_rechazada(): void
    {
        // Usuario con permiso de lectura pero sin permiso de creación
        $user = $this->usuarioSinRol();
        $user->givePermissionTo('tipos-contratos.ver');

        $this->actingAs($user)
            ->post(route('tipos-contratos.store'), [
                'codigo' => 'X1',
                'nombre' => 'No permitido',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('tipos_contratos', ['codigo' => 'X1']);
    }

    public function test_lectura_con_solo_permiso_ver_es_permitida(): void
    {
        $user = $this->usuarioSinRol();
        $user->givePermissionTo('tipos-contratos.ver');

        $this->actingAs($user)
            ->get(route('tipos-contratos.index'))
            ->assertOk();
    }

    public function test_accion_especial_usa_su_permiso_literal(): void
    {
        $cliente = Cliente::create(['codigo' => 'C1', 'nombre' => 'Cliente']);
        $factura = Factura::create([
            'numero' => 9001,
            'fecha_emision' => now(),
            'id_cliente' => $cliente->id,
            'flete_mt' => 10,
            'flete_mlc' => 10,
            'flete_demora' => 0,
            'otros_mt' => 0,
            'ingreso_mt' => 20,
            'estado' => 'emitida',
        ]);

        // TECNICA no tiene facturas.cancelar
        $this->actingAs($this->usuarioConRol('TECNICA'))
            ->post(route('facturas.cancelar', $factura), ['motivo' => 'Prueba'])
            ->assertForbidden();

        // COMERCIAL sí tiene facturas.cancelar
        $this->actingAs($this->usuarioConRol('COMERCIAL'))
            ->post(route('facturas.cancelar', $factura), ['motivo' => 'Prueba'])
            ->assertRedirect();

        $this->assertTrue($factura->fresh()->cancelada);
    }

    public function test_api_kpis_requiere_permiso_dashboard(): void
    {
        $this->actingAs($this->usuarioSinRol())
            ->getJson(route('api.kpis'))
            ->assertForbidden();

        $this->actingAs($this->usuarioConRol('TECNICA'))
            ->getJson(route('api.kpis'))
            ->assertOk();
    }

    public function test_api_pizarra_requiere_permiso_pizarra(): void
    {
        $this->actingAs($this->usuarioSinRol())
            ->getJson(route('api.pizarra'))
            ->assertForbidden();

        $this->actingAs($this->usuarioConRol('TECNICA'))
            ->getJson(route('api.pizarra'))
            ->assertOk();
    }

    public function test_rutas_sin_permiso_catalogado_siguen_accesibles(): void
    {
        // notificaciones no tiene permiso catalogado: fail-open para autenticados
        $this->actingAs($this->usuarioSinRol())
            ->get(route('notificaciones.index'))
            ->assertOk();
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('facturas.index'))->assertRedirect(route('login'));
    }
}
